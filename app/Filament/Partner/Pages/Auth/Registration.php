<?php

namespace App\Filament\Partner\Pages\Auth;

use App\Enums\BusinessPartnerTypeEnum;
use App\Enums\TaxCategoryEnum;
use App\Models\BusinessPartner;
use App\Models\Country;
use App\Models\CustomerType;
use App\Models\Industry;
use App\Models\PartnerRole;
use App\Models\PartnerType;
use App\Models\Region;
use App\Models\User;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class Registration extends Register
{
    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Business Partner Details')
                        ->schema([
                            $this->getRequestTypeFormComponent(),
                            $this->getPartnerGroupFormComponent(),
                            $this->getGlobalLocationNumberFormComponent(),
                            $this->getBusinessPartnerNameFormComponent(),
                            $this->getCityFormComponent(),
                            $this->getStreetFormComponent(),
                            $this->getBuildingFormComponent(),
                            $this->getPostalCodeFormComponent(),
                            $this->getCountryFormComponent(),
                            $this->getRegionFormComponent(),
                            $this->getRegistrationNumberFormComponent(),
                            $this->getTaxNumberFormComponent(),
                            $this->getIndustryTypeFormComponent(),
                            // $this->getCustomerTypeFormComponent(),
                            // $this->getTaxCategoryFormComponent(),
                            $this->getMobileFormComponent(),
                            $this->getBusinessPartnerEmailFormComponent(),
                            $this->getCommercialRegistrationFormComponent(),
                            $this->getTaxCardFormComponent(),
                            $this->getTermsAndConditionsFormComponent(),
                        ]),
                    Step::make('User Details')
                        ->schema([
                            $this->getNameFormComponent(),
                            $this->getEmailFormComponent(),
                            $this->getPasswordFormComponent(),
                            $this->getPasswordConfirmationFormComponent(),
                        ]),
                ])->submitAction($this->getRegisterFormAction()),
            ]);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getRequestTypeFormComponent(): Component
    {
        return Select::make('request_type')
            ->label(__('Partner Type'))
            ->options(
                collect(BusinessPartnerTypeEnum::cases())
                    ->mapWithKeys(fn (BusinessPartnerTypeEnum $case) => [$case->value => $case->label()])
            )
            ->required()
            ->reactive()
            ->live();
    }

    protected function getPartnerGroupFormComponent(): Component
    {
        return Select::make('partner_type_id')
            ->label(__('Partner Group'))
            ->options(
                fn ($get) => $get('request_type')
                    ? PartnerType::where('type', $get('request_type'))->pluck('name_en', 'id')
                    : []
            )
            ->searchable()
            ->required()
            ->disabled(fn ($get) => ! $get('request_type'));
    }

    protected function getGlobalLocationNumberFormComponent(): Component
    {
        return TextInput::make('global_location_number')
            ->label(__('Global Location Number (GLN)'))
            ->hint(__('Exactly 13 characters'))
            ->length(13)
            // ->required()
            ->helperText(__('Unique 13-digit identifier for your location'));
    }

    protected function getBusinessPartnerNameFormComponent(): Component
    {
        return TextInput::make('bp_name')
            ->label(__('Business Partner Name'))
            ->hint(__('Max 80 characters.'))
            ->maxLength(80)
            ->required();
    }

    protected function getCityFormComponent(): Component
    {
        return TextInput::make('city')
            ->label(__('City'))
            ->required()
            ->maxLength(255);
    }

    protected function getStreetFormComponent(): Component
    {
        return TextInput::make('street')
            ->label(__('Street'))
            ->required()
            ->maxLength(255);
    }

    protected function getBuildingFormComponent(): Component
    {
        return TextInput::make('building')
            ->label(__('Building'))
            ->required()
            ->maxLength(10);
    }

    protected function getPostalCodeFormComponent(): Component
    {
        return TextInput::make('postal_code')
            ->label(__('Postal Code'))
            ->required()
            ->maxLength(10);
    }

    protected function getCountryFormComponent(): Component
    {
        return Select::make('country_id')
            ->label(__('Country'))
            ->options(Country::pluck('name_en', 'id'))
            ->searchable()
            ->required()
            ->reactive()
            ->afterStateUpdated(fn ($set) => $set('region_id', null));
    }

    protected function resetRegionField(): void
    {
        $this->dispatch('reset-form-field', name: 'region_id');
    }

    protected function getRegionFormComponent(): Component
    {
        return Select::make('region_id')
            ->label(__('Region'))
            ->options(
                fn ($get) => $get('country_id')
                    ? Region::where('country_id', $get('country_id'))->pluck('name_en', 'id')
                    : []
            )
            ->searchable()
            ->required()
            ->disabled(fn ($get) => ! $get('country_id'));
    }

    protected function getRegistrationNumberFormComponent(): Component
    {
        return TextInput::make('registration_number')
            ->label(__('Registration Number'))
            ->required()
            ->maxLength(255);
    }

    protected function getTaxNumberFormComponent(): Component
    {
        return TextInput::make('tax_number')
            ->label(__('Tax Number'))
            ->required()
            ->maxLength(255);
    }

    protected function getIndustryTypeFormComponent(): Component
    {
        return Select::make('industry_id')
            ->label(__('Industry Type'))
            ->options(Industry::pluck('name_en', 'id'))
            ->searchable()
            ->visible(
                fn ($get) => $get('request_type') === BusinessPartnerTypeEnum::Customer->value
            )
            ->required();
    }

    // protected function getCustomerTypeFormComponent(): Component
    // {
    //     return Select::make('customer_type_id')
    //         ->label(__('Customer Type'))
    //         ->options(CustomerType::pluck('name_en', 'id'))
    //         ->searchable()

    //         ->visible(
    //             fn($get) =>
    //             $get('request_type')=== BusinessPartnerTypeEnum::Customer->value
    //         )
    //         ->required();
    // }

    // protected function getTaxCategoryFormComponent(): Component
    // {
    //     return Select::make('tax_category')
    //         ->label(__('Tax Category'))
    //         ->options(
    //             collect(TaxCategoryEnum::cases())
    //                 ->mapWithKeys(fn(TaxCategoryEnum $case) => [$case->value => $case->label()])
    //         )
    //         ->searchable()
    //         ->required();
    // }

    protected function getMobileFormComponent(): Component
    {
        return PhoneInput::make('mobile')
            ->label(__('Mobile Number'))
            ->required()
            ->onlyCountries(['eg']);
    }

    protected function getBusinessPartnerEmailFormComponent(): Component
    {
        return TextInput::make('business_partner_email')
            ->label(__('Email'))
            ->email()
            ->required()
            ->maxLength(255);
    }

    protected function getCommercialRegistrationFormComponent(): Component
    {
        return FileUpload::make('commercial_registration')
            ->label(__('Commercial Registration Document'))
            // ->required()
            ->acceptedFileTypes(['application/pdf'])
            ->maxSize(5120)
            ->storeFiles(false)
            ->helperText(__('PDF only. Max 5MB.'));
    }

    protected function getTaxCardFormComponent(): Component
    {
        return FileUpload::make('tax_card')
            ->label(__('Tax Card Document'))
            // ->required()
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->maxSize(5120)
            ->storeFiles(false)
            ->helperText(__('PDF, JPEG, or PNG. Max 5MB.'));
    }

    protected function getTermsAndConditionsFormComponent(): Checkbox
    {
        return Checkbox::make('terms')
            ->label(__('I agree to the terms and conditions.'))
            ->required()
            ->accepted()
            ->validationMessages([
                'accepted' => __('You must accept the terms and conditions to register.'),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['commercial_registration']) && $data['commercial_registration'] instanceof TemporaryUploadedFile) {
                $folder = 'partner-documents'; // The directory inside the disk
                $filename = time().'-'.$data['commercial_registration']->getClientOriginalName();

                $commercialRegistrationPath = $data['commercial_registration']->storeAs(
                    $folder,
                    $filename,
                    'private'
                );
            }
            if (isset($data['tax_card']) && $data['tax_card'] instanceof TemporaryUploadedFile) {
                $folder = 'partner-documents'; // The directory inside the disk
                $filename = time().'-'.$data['tax_card']->getClientOriginalName();

                $taxCardPath = $data['tax_card']->storeAs(
                    $folder,
                    $filename,
                    'private'
                );
            }
            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'type' => $data['request_type'],
            ]);

            // Parse GLN (13 characters total)
            $gln = $data['global_location_number'];
            $gln1 = substr($gln, 0, 7);
            $gln2 = substr($gln, 7, 5);
            $ckgdigit = substr($gln, 12, 1);

            // Split name into name1 and name2
            $fullName = $data['bp_name'];
            $name1 = substr($fullName, 0, 40);
            $name2 = strlen($fullName) > 40 ? substr($fullName, 40, 40) : null;

            // Create business partner
            $businessPartner = BusinessPartner::create([
                'user_id' => $user->id,
                // 'request_id' => 9,
                'type' => $data['request_type'],
                'partner_type_id' => $data['partner_type_id'],
                'gln1' => $gln1,
                'gln2' => $gln2,
                'ckgdigit' => $ckgdigit,
                'name1' => ''.$name1,
                'name2' => ''.$name2,
                'city' => $data['city'],
                'street' => $data['street'],
                'building' => $data['building'],
                'postal_code' => $data['postal_code'],
                'country_id' => $data['country_id'],
                'region_id' => $data['region_id'],
                'registration_number' => $data['registration_number'],
                'tax_number' => $data['tax_number'],
                'industry_id' => $data['industry_id'] ?? null,
                // 'customer_type_id' => $data['customer_type_id'],
                // 'tax_category' => $data['tax_category'],
                'mobile' => $data['mobile'],
                'email' => $data['business_partner_email'],
                'commercial_registration' => $commercialRegistrationPath ?? null,
                'tax_card' => $taxCardPath ?? null,
                'status' => 'P',
            ]);

            $user->business_partner_id = $businessPartner->id;
            $user->save();
            $user->assignRole('partner');
            $user->partnerRoles()->attach(PartnerRole::pluck('id'));

            $businessPartner->submitToSap();

            return $user;
        });
    }
}
