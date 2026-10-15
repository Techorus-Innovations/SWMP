<?php

namespace App\Filament\Partner\Pages;

use App\Models\Client;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiCredentials extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'API Credentials';

    protected static ?string $title = 'API Credentials';

    protected string $view = 'filament.partner.pages.api-credentials';

    public ?string $plainSecret = null;

    public static function canAccess(): bool
    {
        $user = filament()->auth()->user();
        if (! $user) {
            return false;
        }

        $partner = $user->businessPartner;
        if (! $partner) {
            return false;
        }

        // Only allow primary partner user and active approved partners
        $isOwner = ((string) $partner->user_id === (string) $user->id);
        $isApproved = ($partner->status === 'A' && ! empty($partner->bp_code));

        return $isOwner && $isApproved;
    }

    public function getClientProperty(): ?Client
    {
        return filament()->auth()->user()?->businessPartner?->client()->first();
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            // Reveal banner when a new secret is generated or regenerated
            Section::make('Your New Client Secret')
                ->description('Copy this secret now. For security reasons, it will not be displayed again.')
                ->visible(fn (): bool => filled($this->plainSecret))
                ->schema([
                    TextEntry::make('new_client_secret')
                        ->label('Client Secret')
                        ->state(fn () => $this->plainSecret)
                        ->copyable()
                        ->copyMessage('Client Secret copied to clipboard')
                        ->weight('bold')
                        ->color('success'),
                ]),

            // Active Credentials Details
            Section::make('API Client Credentials')
                ->description('Use these credentials to authenticate your external system or ERP.')
                ->visible(fn (): bool => (bool) $this->client)
                ->columns(2)
                ->schema([
                    TextEntry::make('client_id')
                        ->label('Client ID')
                        ->state(fn () => $this->client?->client_id)
                        ->copyable()
                        ->copyMessage('Client ID copied to clipboard'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->state(fn () => $this->client?->is_active ? 'Active' : 'Deactivated')
                        ->color(fn () => $this->client?->is_active ? 'success' : 'danger'),

                    TextEntry::make('created_at')
                        ->label('Created')
                        ->state(fn () => $this->client?->created_at?->format('Y-m-d H:i') ?? 'N/A'),

                    TextEntry::make('last_used_at')
                        ->label('Last Used')
                        ->state(fn () => $this->client?->last_used_at?->diffForHumans() ?? 'Never'),
                ]),

            // Empty state when no credentials exist yet
            Section::make('No API Credentials Found')
                ->description('Generate an API client credential to allow external software, ERPs, or webhooks to integrate with your SWMP account.')
                ->visible(fn (): bool => ! $this->client)
                ->schema([
                    TextEntry::make('status_info')
                        ->hiddenLabel()
                        ->state('Click the "Generate API Credentials" button above to create your Client ID and Client Secret.'),
                ]),
        ]);
    }

    public function generateCredentials(): void
    {
        $partner = filament()->auth()->user()?->businessPartner;

        if (! $partner) {
            return;
        }

        if ($partner->client()->exists()) {
            Notification::make()
                ->title('Credentials already exist')
                ->warning()
                ->send();
            return;
        }

        $clientId = 'cl_' . Str::lower(Str::random(24));
        $clientSecret = 'sec_' . Str::random(32);

        $client = Client::create([
            'business_partner_id' => $partner->id,
            'name' => $partner->name1 . ' API Integration',
            'client_id' => $clientId,
            'client_secret' => Hash::make($clientSecret),
            'is_active' => true,
        ]);

        unset($this->client);
        $partner->unsetRelation('client');

        $this->plainSecret = $clientSecret;

        Notification::make()
            ->title('API Credentials Generated')
            ->body('Copy your Client Secret now. For security, it will not be displayed again.')
            ->success()
            ->persistent()
            ->send();
    }

    public function regenerateSecret(): void
    {
        $partner = filament()->auth()->user()?->businessPartner;
        $client = $partner?->client()->first();

        if (! $client) {
            return;
        }

        $clientSecret = 'sec_' . Str::random(32);

        $client->update([
            'client_secret' => Hash::make($clientSecret),
        ]);

        // Revoke all previously active Sanctum tokens
        $client->tokens()->delete();

        unset($this->client);
        $partner?->unsetRelation('client');

        $this->plainSecret = $clientSecret;

        Notification::make()
            ->title('Client Secret Regenerated')
            ->body('All previous active tokens have been revoked. Copy your new Client Secret now.')
            ->warning()
            ->persistent()
            ->send();
    }

    public function activateAccess(): void
    {
        $partner = filament()->auth()->user()?->businessPartner;
        $client = $partner?->client()->first();

        if (! $client) {
            return;
        }

        $client->update([
            'is_active' => true,
        ]);

        unset($this->client);
        $partner?->unsetRelation('client');

        Notification::make()
            ->title('API Access Activated')
            ->body('API credentials are now active.')
            ->success()
            ->send();
    }

    public function deactivateAccess(): void
    {
        $partner = filament()->auth()->user()?->businessPartner;
        $client = $partner?->client()->first();

        if (! $client) {
            return;
        }

        $client->update([
            'is_active' => false,
        ]);

        // Revoke all active tokens
        $client->tokens()->delete();

        unset($this->client);
        $partner?->unsetRelation('client');

        Notification::make()
            ->title('API Access Deactivated')
            ->body('API access is revoked and active tokens have been cleared.')
            ->danger()
            ->send();
    }

    public function toggleActive(): void
    {
        if ($this->client?->is_active) {
            $this->deactivateAccess();
        } else {
            $this->activateAccess();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate API Credentials')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(fn (): bool => ! $this->client)
                ->action('generateCredentials'),

            Action::make('regenerate')
                ->label('Regenerate Secret')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => (bool) $this->client)
                ->requiresConfirmation()
                ->modalHeading('Regenerate Client Secret?')
                ->modalDescription('Regenerating your secret will immediately invalidate and revoke all active access tokens. Any automated systems using the previous secret will need to be updated.')
                ->modalSubmitActionLabel('Yes, regenerate')
                ->action('regenerateSecret'),

            Action::make('activate')
                ->label('Activate Access')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => (bool) ($this->client && ! $this->client->is_active))
                ->requiresConfirmation()
                ->modalHeading('Activate API Access?')
                ->modalDescription('This will allow your client credentials to request API access tokens again.')
                ->modalSubmitActionLabel('Yes, activate')
                ->action('activateAccess'),

            Action::make('deactivate')
                ->label('Deactivate Access')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (): bool => (bool) ($this->client && $this->client->is_active))
                ->requiresConfirmation()
                ->modalHeading('Deactivate API Access?')
                ->modalDescription('This will immediately revoke all active access tokens and reject future API calls.')
                ->modalSubmitActionLabel('Yes, deactivate')
                ->action('deactivateAccess'),
        ];
    }
}
