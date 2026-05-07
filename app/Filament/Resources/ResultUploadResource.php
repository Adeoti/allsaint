<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Subject;
use Filament\Forms\Form;
use App\Models\ResultRoot;
use Filament\Tables\Table;
use App\Models\SchoolClass;
use App\Models\ResultUpload;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ResultUploadResource\Pages;
use App\Filament\Resources\ResultUploadResource\Pages\CreateResultUpload;
use App\Filament\Resources\ResultUploadResource\Pages\EditResultUpload;
use App\Filament\Resources\ResultUploadResource\Widgets\ManualEntryForm;

class ResultUploadResource extends Resource
{
    protected static ?string $model = ResultUpload::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square-stack';
    protected static ?string $navigationGroup = 'Examinations';
    protected static ?string $navigationLabel = 'Result Uploads';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Result Details')
                    ->schema([
                        Select::make('result_root_id')
                            ->label('Result Root')
                            ->required()
                            ->options(ResultRoot::orderBy('created_at', 'desc')->get()->pluck('name', 'id'))
                            ->reactive(),

                        Select::make('class_id')
                            ->label('Class')
                            ->required()
                            ->options(function (callable $get) {
                                $resultRootId = $get('result_root_id');
                                if (!$resultRootId) {
                                    return SchoolClass::all()->pluck('name', 'id');
                                }

                                $resultRoot = ResultRoot::find($resultRootId);
                                return SchoolClass::whereNotNull('name')
                                    ->get()
                                    ->pluck('name', 'id');
                            })
                            ->reactive(),

                        Select::make('subject_id')
                            ->label('Subject')
                            ->required()
                            ->options(Subject::all()->pluck('name', 'id')),
                    ])->columns(2),

                FileUpload::make('file_path')
                    ->label('CSV File')
                    ->acceptedFileTypes(['text/csv', 'text/plain'])
                    ->directory('result_uploads')
                    ->visibility('private')
                    ->maxSize(1024)
                    ->required()
                    ->columnSpanFull(),

            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->query(
                ResultUpload::query()->orderBy('created_at', 'desc')
            )
            ->headerActions([
                Tables\Actions\Action::make('manual_entry')
                    ->label('Manual Result Entry')
                    ->icon('heroicon-o-pencil-square')
                    ->url(route('filament.admin.resources.result-uploads.manual-entry')),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('resultRoot.name')
                    ->label('Result Root')
                    ->sortable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('entry_type')
                    ->label('Method')
                    ->colors([
                        'success' => 'csv',
                        'warning' => 'manual',
                    ])
                    ->formatStateUsing(fn($state) => strtoupper($state)),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('entry_type')
                    ->options([
                        'csv' => 'CSV Upload',
                        'manual' => 'Manual Entry',
                    ]),
                Tables\Filters\SelectFilter::make('result_root_id')
                    ->label('Result Root')
                    ->options(ResultRoot::all()->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Class')
                    ->options(SchoolClass::all()->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_results')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn(ResultUpload $record): string => route('report-cards.show', ['record' => $record->result_root_id]))
                    ->openUrlInNewTab(),
                // ->url(
                //     fn(ResultUpload $record) =>
                //     route('filament.admin.resources.result-roots.view-results', [
                //         'record' => $record->result_root_id,
                //         'class' => $record->class_id,
                //         'subject' => $record->subject_id
                //     ])
                // ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResultUploads::route('/'),
            'create' => Pages\CreateResultUpload::route('/create'),
            'edit' => Pages\EditResultUpload::route('/{record}/edit'),


        ];
    }
}
