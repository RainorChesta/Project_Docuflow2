<?php

namespace App\Providers;

use App\AI\Contracts\AIClientInterface;
use App\AI\AIFallbackManager;
use App\AI\GroqClient;
use App\AI\DeepseekClient;
use App\AI\OllamaClient;
use App\Models\Document;
use App\Models\User;
use App\Policies\DocumentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GroqClient::class, function () {
            if (!config('services.groq.key')) {
                return null;
            }
            return new GroqClient(
                apiKey: (string) config('services.groq.key'),
                model: (string) config('services.groq.model'),
            );
        });

        $this->app->singleton(DeepseekClient::class, function () {
            if (!config('services.deepseek.key')) {
                return null;
            }
            return new DeepseekClient(
                apiKey: (string) config('services.deepseek.key'),
                model: (string) config('services.deepseek.model', 'deepseek-chat'),
            );
        });

        $this->app->singleton(OllamaClient::class, function () {
            if (!config('services.ollama.url')) {
                return null;
            }
            return new OllamaClient(
                baseUrl: (string) config('services.ollama.url'),
                model: (string) config('services.ollama.model')
            );
        });

        $this->app->bind(AIClientInterface::class, function ($app) {
            $clients = [];

            if ($groq = $app->make(GroqClient::class)) {
                $clients[] = $groq;
            }

            if ($deepseek = $app->make(DeepseekClient::class)) {
                $clients[] = $deepseek;
            }

            if ($ollama = $app->make(OllamaClient::class)) {
                $clients[] = $ollama;
            }

            return new AIFallbackManager($clients);
        });
    }

    public function boot(): void
    {
        Gate::define('admin', fn(User $user) => $user->isAdmin());

        Gate::policy(Document::class, DocumentPolicy::class);

        // Observers
        \App\Models\SignatureRequest::observe(\App\Observers\SignatureRequestObserver::class);
    }
}
