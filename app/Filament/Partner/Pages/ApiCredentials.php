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
        return filament()->auth()->user()?->businessPartner?->client;
    }

    public function infolist(Schema $schema): Schema
    {
        $client = $this->client;
        $components = [];

        // Reveal banner when a new secret is generated or regenerated
        if ($this->plainSecret) {
            $components[] = Section::make('Your New Client Secret')
                ->description('Copy this secret now. For security reasons, it will not be displayed again.')
                ->schema([
                    TextEntry::make('new_client_secret')
                        ->label('Client Secret')
                        ->state($this->plainSecret)
                        ->copyable()
                        ->copyMessage('Client Secret copied to clipboard')
                        ->weight('bold')
                        ->color('success'),
                ]);
        }

        // Active Credentials Details
        if ($client) {
            $components[] = Section::make('API Client Credentials')
                ->description('Use these credentials to authenticate your external system or ERP.')
                ->columns(2)
                ->schema([
                    TextEntry::make('client_id')
                        ->label('Client ID')
                        ->state($client->client_id)
                        ->copyable()
                        ->copyMessage('Client ID copied to clipboard'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->state($client->is_active ? 'Active' : 'Deactivated')
                        ->color($client->is_active ? 'success' : 'danger'),

                    TextEntry::make('created_at')
                        ->label('Created')
                        ->state($client->created_at?->format('Y-m-d H:i') ?? 'N/A'),

                    TextEntry::make('last_used_at')
                        ->label('Last Used')
                        ->state($client->last_used_at?->diffForHumans() ?? 'Never'),
                ]);

            // $components[] = Section::make('Authentication Details')
                // ->description('How to exchange your credentials for an API Bearer token.')
                // ->columns(2)
                // ->schema([
                //     TextEntry::make('token_endpoint')
                //         ->label('Token Endpoint')
                //         ->state(url('/api/v1/auth/token'))
                //         ->copyable()
                //         ->copyMessage('Token endpoint copied to clipboard'),

                //     TextEntry::make('token_lifetime')
                //         ->label('Token Lifetime')
                //         ->state('24 Hours (86,400 seconds)'),

                //     TextEntry::make('method')
                //         ->label('HTTP Method')
                //         ->badge()
                //         ->state('POST')
                //         ->color('info'),

                //     TextEntry::make('documentation')
                //         ->label('OpenAPI Documentation')
                //         ->state('public/docs/openapi.yaml'),
                // ]);
        } else {
            // Empty state when no credentials exist yet
            $components[] = Section::make('No API Credentials Found')
                ->description('Generate an API client credential to allow external software, ERPs, or webhooks to integrate with your SWMP account.')
                ->schema([
                    TextEntry::make('status_info')
                        ->hiddenLabel()
                        ->state('Click the "Generate API Credentials" button above to create your Client ID and Client Secret.'),
                ]);
        }

        return $schema->components($components);
    }

    public function generateCredentials(): void
    {
        $partner = filament()->auth()->user()?->businessPartner;

        if (! $partner) {
            return;
        }

        if ($partner->client) {
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
        $client = $this->client;

        if (! $client) {
            return;
        }

        $clientSecret = 'sec_' . Str::random(32);

        $client->update([
            'client_secret' => Hash::make($clientSecret),
        ]);

        // Revoke all previously active Sanctum tokens
        $client->tokens()->delete();

        $this->plainSecret = $clientSecret;

        Notification::make()
            ->title('Client Secret Regenerated')
            ->body('All previous active tokens have been revoked. Copy your new Client Secret now.')
            ->warning()
            ->persistent()
            ->send();
    }

    public function toggleActive(): void
    {
        $client = $this->client;

        if (! $client) {
            return;
        }

        $newStatus = ! $client->is_active;

        $client->update([
            'is_active' => $newStatus,
        ]);

        if (! $newStatus) {
            $client->tokens()->delete();
        }

        Notification::make()
            ->title($newStatus ? 'API Access Activated' : 'API Access Deactivated')
            ->body($newStatus ? 'API credentials are now active.' : 'API access is revoked and active tokens have been cleared.')
            ->status($newStatus ? 'success' : 'danger')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $client = $this->client;

        if (! $client) {
            return [
                Action::make('generate')
                    ->label('Generate API Credentials')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->action('generateCredentials'),
            ];
        }

        return [
            Action::make('regenerate')
                ->label('Regenerate Secret')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerate Client Secret?')
                ->modalDescription('Regenerating your secret will immediately invalidate and revoke all active access tokens. Any automated systems using the previous secret will need to be updated.')
                ->modalSubmitActionLabel('Yes, regenerate')
                ->action('regenerateSecret'),

            Action::make('toggle')
                ->label($client->is_active ? 'Deactivate Access' : 'Activate Access')
                ->icon($client->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                ->color($client->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action('toggleActive'),
        ];
    }
}
