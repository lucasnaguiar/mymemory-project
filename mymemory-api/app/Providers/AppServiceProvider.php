<?php

namespace App\Providers;

use App\Contracts\AiProviderInterface;
use App\Models\Memo;
use App\Policies\MemoPolicy;
use App\Services\Ai\OpenAiProvider;
use App\Services\Ai\StubAiProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiProviderInterface::class, function () {
            $provider = config('ai.provider', 'stub');
            $apiKey   = config('ai.openai_key', '');

            if ($provider === 'openai' && $apiKey !== '') {
                return new OpenAiProvider($apiKey);
            }

            return new StubAiProvider();
        });
    }

    public function boot(): void
    {
        Gate::policy(Memo::class, MemoPolicy::class);
    }
}
