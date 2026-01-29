<?php

namespace App\Filament\Pages\Auth;

use App\Models\Company;
use App\Models\User;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\Checkbox;
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
                        ]),
                    Step::make('Company Details')
                        ->schema([
                            $this->getCompanyNameFormComponent(),
                            $this->getCompanyPhoneFormComponent(),
                            $this->getCompanyLocationFormComponent(),
                            $this->getTermsAndConditionsFormComponent(),
                        ]),
                ])->submitAction($this->getRegisterFormAction()),
            ]);
    }

    protected function getFormActions(): array
    {
        return [];
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

    protected function getCompanyNameFormComponent(): Component
    {
        return TextInput::make('company_name')
            ->label(__('Company Name'))
            ->required()
            ->maxLength(255);
    }

    protected function getCompanyPhoneFormComponent(): Component
    {
        return TextInput::make('company_phone')
            ->label(__('Company Phone Number'))
            ->required()
            ->maxLength(255);
    }

    protected function getCompanyLocationFormComponent(): Component
    {
        return TextInput::make('company_location')
            ->label(__('Company Location'))
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
                'role' => 'partner',
            ]);

            $company = Company::create([
                'company_name' => $data['company_name'],
                'phone_number' => $data['company_phone'],
                'location' => $data['company_location'],
                'user_id' => $user->id,
            ]);
            $user->company_id = $company->id;
            $user->save();

            return $user;
        });
    }
}