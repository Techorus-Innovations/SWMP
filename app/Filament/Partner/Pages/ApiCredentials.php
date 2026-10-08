<?php

namespace App\Filament\Partner\Pages;

use App\Models\Client;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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
