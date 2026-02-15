<?php

declare(strict_types=1);

namespace App\Services\Overrides\Pages;

use Filament\Auth\Pages\Login as FilamentLogin;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class Login extends FilamentLogin
{
    public function form(Schema $schema): Schema
    {
        /** @var \Filament\Forms\Components\TextInput $email */
        $email = $this->getEmailFormComponent();

        /** @var \Filament\Forms\Components\TextInput $password */
        $password = $this->getPasswordFormComponent();

        return $schema
            ->components([
                $email
                    ->label('المعرف')
                    ->extraInputAttributes(['dir' => 'ltr']),
                $password->extraInputAttributes(['dir' => 'ltr']),
                $this->getRememberFormComponent(),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }
}
