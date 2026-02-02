<?php

namespace App\Filament\Pages\Auth;

use App\Enums\BusinessPartnerTypeEnum;
use App\Enums\UserTypeEnum;
use App\Models\BusinessPartner;
use App\Models\User;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Registration extends Register
{
    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('User Details')
                        ->schema([
                            $this->getNameFormComponent(),
                            $this->getEmailFormComponent(),
                            $this->getPasswordFormComponent(),
                            $this->getPasswordConfirmationFormComponent(),
                            $this->getUserTypeFormComponent(),
                        ]),
                    Step::make('Business Partner Details')
                        ->schema([
                            $this->getBusinessPartnerNameFormComponent(),
                            $this->getBusinessPartnerPhoneFormComponent(),
                            $this->getBusinessPartnerLocationFormComponent(),
                            $this->getTermsAndConditionsFormComponent(),
                        ]),
                ])->submitAction($this->getRegisterFormAction()),
            ]);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getUserTypeFormComponent(): Component
    {
        return Select::make('type')
            ->label(__('User Type'))
            ->options(['c' => UserTypeEnum::Customer->label(), 's' => UserTypeEnum::Supplier->label()])
            ->options(
                collect(UserTypeEnum::cases())
                    ->filter(fn (UserTypeEnum $case) => !in_array($case, [UserTypeEnum::Admin]))
                    ->mapWithKeys(fn (UserTypeEnum $case) => [$case->value => $case->label()])
            )
            ->required();
    }

    protected function getTermsAndConditionsFormComponent(): Checkbox
    {
        return Checkbox::make('terms')
            ->label(__('I agree to the terms and conditions'))
            ->required()
            ->accepted()
            ->validationMessages([
                'accepted' => __('You must accept the terms and conditions to register.'),
            ]);
    }

    protected function getBusinessPartnerNameFormComponent(): Component
    {
        return TextInput::make('business_partner_name')
            ->label(__('Business Partner Name'))
            ->required()
            ->maxLength(255);
    }

    protected function getBusinessPartnerPhoneFormComponent(): Component
    {
        return TextInput::make('business_partner_phone')
            ->label(__('Business Partner Phone Number'))
            ->required()
            ->maxLength(255);
    }

    protected function getBusinessPartnerLocationFormComponent(): Component
    {
        return TextInput::make('business_partner_location')
            ->label(__('Business Partner Location'))
            ->required()
            ->maxLength(255);
    }


    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => $data['type'],
            ]);
            $businessPartner = BusinessPartner::create([
                'name' => $data['business_partner_name'],
                'phone_number' => $data['business_partner_phone'],
                'location' => $data['business_partner_location'],
                'type' => $data['type'],
                'user_id' => $user->id,
            ]);
            $user->business_partner_id = $businessPartner->id;
            $user->save();

            return $user;
        });
    }
}