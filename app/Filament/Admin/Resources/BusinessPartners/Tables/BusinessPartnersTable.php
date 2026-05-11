<?php

namespace App\Filament\Admin\Resources\BusinessPartners\Tables;

use App\Enums\BusinessPartnerTypeEnum;
use App\Enums\TaxCategoryEnum;
use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BusinessPartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_id')
                    ->label(__('Request ID'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name1')
                    ->label(__('Business Partner Name'))
                    ->getStateUsing(fn ($record) => $record->name1.' '.$record->name2)
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('Request Type'))
                    ->formatStateUsing(fn (BusinessPartnerTypeEnum $state): string => $state->label())
                    ->searchable(),
                TextColumn::make('partnerType.name_en')
                    ->label(__('Partner Group'))
                    ->searchable(),
                TextColumn::make('city')
                    ->label(__('City'))
                    ->searchable(),
                TextColumn::make('country.name_en')
                    ->label(__('Country'))
                    ->searchable(),
                TextColumn::make('mobile')
                    ->label(__('Mobile'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('Super Admin User'))
                    ->searchable()
                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record->user])),
                TextColumn::make('registration_number')
                    ->label(__('Registration Number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tax_number')
                    ->label(__('Tax Number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('industry.name_en')
                    ->label(__('Industry'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('customerType.name_en')
                    ->label(__('Customer Type'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tax_category')
                    ->label(__('Tax Category'))
                    ->formatStateUsing(fn (TaxCategoryEnum $state) => $state->label())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('street')
                    ->label(__('Street'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('building')
                    ->label(__('Building'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('postal_code')
                    ->label(__('Postal Code'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('region.name_en')
                    ->label(__('Region'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
