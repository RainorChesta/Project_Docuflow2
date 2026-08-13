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
        $this->app->bind(AIClientInterface::class, function () {
            $clients = [
                new GroqClient(
                    apiKey: (string) config('services.groq.key'),
                    model: (string) config('services.groq.model'),
                ),
            ];

            if (config('services.deepseek.key')) {
                $clients[] = new DeepseekClient(
                    apiKey: (string) config('services.deepseek.key'),
                    model: (string) config('services.deepseek.model', 'deepseek-chat'),
                );
            }

            if (config('services.ollama.url')) {
                $clients[] = new OllamaClient(
                    baseUrl: (string) config('services.ollama.url'),
                    model: (string) config('services.ollama.model')
                );
            }

            return new AIFallbackManager($clients);
        });
    }

    public function boot(): void
    {
        Gate::define('admin', fn(User $user) => $user->isAdmin());

        Gate::policy(Document::class, DocumentPolicy::class);
    }
}
