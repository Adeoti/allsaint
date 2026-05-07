<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Branch;
use Filament\Forms\Form;
use App\Models\ResultRoot;
use Filament\Tables\Table;
use App\Models\GradingSystem;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ResultRootResource\Pages;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Filament\Resources\ResultRootResource\RelationManagers;
use App\Filament\Resources\ResultRootResource\Pages\ViewResultsPage;
use Faker\Core\File;
use Filament\Forms\Components\FileUpload;

class ResultRootResource extends Resource
{
    protected static ?string $model = ResultRoot::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationGroup = 'Examinations';
    protected static ?string $navigationLabel = 'Result Root';




    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('grading_system_id')
                            ->label('Grading System')
                            ->options(GradingSystem::all()->pluck('name', 'id'))
                            ->required(),

                        DatePicker::make('next_term')
                            ->required()
                            ->label('Next Term Begins'),

                        Select::make('teacher_id')
                            ->label('Class Teacher')
                            ->options(function () {
                                return \App\Models\User::whereHas('teacher')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('branch_ids')
                            ->label('Branches')
                            ->required()
                            ->options(Branch::all()->pluck('name', 'id'))
                            ->multiple()
                            ->columnSpanFull(),
                        Textarea::make('section_address')
                            ->label('Section Address')
                            ->placeholder('Enter the address of the section (e.g., Senior Section, 123 Main St, City, Country)')
                            ->helperText('This address will appear on the result sheets to identify the section.')
                            ->columnSpanFull(),
                        FileUpload::make('logo')
                            ->label('Section Logo')
                            ->image()
                            ->columnSpanFull(),

                        Section::make('Exam Score Columns')
                            ->collapsible()
                            ->description('Create exam scrore columns to use for result calculation')
                            ->schema([
                                Repeater::make('exam_score_columns')
                                    ->label('exam score columns')
                                    ->schema([
                                        TextInput::make('label')->label('Column Label')->placeholder('E.g. 1st CA')->required(),
                                        TextInput::make('overall_score')->label('Overall Score')->numeric()->placeholder('E.g. 100')->required(),

                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])

                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        // Order by created_at descending by default
            ->defaultSort('created_at', 'desc')
            
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->description(function (ResultRoot $record) {
                        return $record->description;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('gradingSystem.name')
                    ->numeric()
                    ->sortable(),
                ViewColumn::make('branch_ids')
                    ->view('tables.columns.branches')
                    ->label('Branches'),
                Tables\Columns\TextColumn::make('next_term')
                    ->date(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([

                Tables\Actions\EditAction::make()
                    ->button(),
                Tables\Actions\Action::make('View Results')
                    ->button()
                    ->color('purple')
                    ->label('View Results')
                    ->url(fn(ResultRoot $record): string => route('report-cards.show', ['record' => $record->id]))
                    ->openUrlInNewTab(),
                // ->action(fn(ResultRoot $record) => redirect()->route('filament.admin.resources.result-roots.view-results', ['record' => $record->id])),

                Tables\Actions\Action::make('CSV template')
                    ->label('Generate CSV Template')
                    ->icon('heroicon-s-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->button()
                    ->action(fn(ResultRoot $record) => static::downloadCsvTemplate($record)),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // protected static function downloadCsvTemplate()
    // {
    //     $headers = [
    //         'Content-Type' => 'text/csv',
    //         'Content-Disposition' => 'attachment; filename="csv_template.csv"',
    //     ];

    //     // Define the columns for your CSV template
    //     $columns = ['Student_ID', 'Column2', 'Column3'];  // Replace with actual column names

    //     $callback = function () use ($columns) {
    //         $file = fopen('php://output', 'w');

    //         // Add the headers for your CSV columns
    //         fputcsv($file, $columns);

    //         // Close the file stream
    //         fclose($file);
    //     };

    //     // Return the CSV as a streamed response
    //     return new StreamedResponse($callback, 200, $headers);
    // }

    protected static function downloadCsvTemplate(ResultRoot $record)
    {
        // Initialize the CSV headers with "Student_ID" as the first column
        $columns = ['Student_ID'];

        // Check if the record has `exam_score_columns` data
        if (is_array($record->exam_score_columns)) {
            // Use the exam_score_columns array directly
            $examScoreColumns = $record->exam_score_columns;

            // Generate columns based on "label" and "overall_score"
            foreach ($examScoreColumns as $column) {
                $label = $column['label'] ?? 'Unknown';
                $overallScore = $column['overall_score'] ?? '0';
                $columns[] = "{$label} - {$overallScore}";
            }
        }

        // Make the file name dynamic using the record's name, replacing spaces with underscores
        $fileName = str_replace(' ', '_', $record->name) . '_template.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');

            // Add the headers for your CSV columns
            fputcsv($file, $columns);

            // Close the file stream
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }




    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResultRoots::route('/'),
            'create' => Pages\CreateResultRoot::route('/create'),
            'edit' => Pages\EditResultRoot::route('/{record}/edit'),
            'view-results' => ViewResultsPage::route('/{record}/view-results'),
        ];
    }
}
