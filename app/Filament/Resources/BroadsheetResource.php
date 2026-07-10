<?php

namespace App\Filament\Resources;

use App\Models\Broadsheet;
use App\Models\SchoolClass;
use App\Models\ResultRoot;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\BadgeColumn;
use App\Filament\Resources\BroadsheetResource\Pages;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;

class BroadsheetResource extends Resource
{
    protected static ?string $model = Broadsheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Examinations';
    protected static ?string $navigationLabel = 'Broadsheets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Broadsheet Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., 2025/2026 1st Term Broadsheet'),
                        Hidden::make('created_by')
                            ->default(Auth::id()),
                        TextInput::make('term')
                            ->required()
                            ->placeholder('e.g., 2025/2026 1st Term'),

                        Select::make('class_id')
                            ->label('Class')
                            ->required()
                            ->options(SchoolClass::all()->pluck('name', 'id'))
                            ->searchable(),

                        Select::make('result_root_ids')
                            ->label('Select Result Roots')
                            ->required()
                            ->multiple()
                            ->options(ResultRoot::all()->pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Select multiple result roots to combine into broadsheet'),

                        Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Optional description for this broadsheet'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Broadsheet::query()->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('term')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->getStateUsing(function (Broadsheet $record) {
                        return $record->generated_data ? 'Generated' : 'Pending';
                    })
                    ->colors([
                        'success' => 'Generated',
                        'warning' => 'Pending',
                    ]),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Class')
                    ->options(SchoolClass::all()->pluck('name', 'id')),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('generate')
                        ->label('Generate')
                        ->icon('heroicon-o-cog')
                        ->color('success')
                        ->action(function (Broadsheet $record) {
                            $record->generateBroadsheetData();

                            \Filament\Notifications\Notification::make()
                                ->title('Broadsheet Generated Successfully!')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Generate Broadsheet')
                        ->modalDescription('This will calculate totals and positions for all students. This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Generate'),

                    Tables\Actions\Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->url(fn(Broadsheet $record): string => route('filament.admin.resources.broadsheets.view', ['record' => $record->id]))
                        ->openUrlInNewTab(),
                        
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),

                    // Tables\Actions\Action::make('download')
                    //     ->label('Download PDF')
                    //     ->icon('heroicon-o-arrow-down-tray')
                    //     ->color('gray')
                    //     ->url(fn(Broadsheet $record): string => route('broadcast.download', ['broadcast' => $record->id])),

                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
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
            'index' => Pages\ListBroadsheets::route('/'),
            'create' => Pages\CreateBroadsheet::route('/create'),
            'edit' => Pages\EditBroadsheet::route('/{record}/edit'),
            // 'view' => Pages\ViewBroadsheet::route('/{record}'),
        ];
    }
}
