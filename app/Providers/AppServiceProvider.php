<?php

namespace App\Providers;

use App\Models\Acesso;
use App\Models\ConfiguracaoSistema;
use App\Models\LogAuditoria;
use App\Models\Pessoa;
use App\Models\PontoAcesso;
use App\Models\User;
use App\Models\Veiculo;
use App\Observers\AuditoriaObserver;
use App\Policies\AcessoPolicy;
use App\Policies\ConfiguracaoSistemaPolicy;
use App\Policies\LogAuditoriaPolicy;
use App\Policies\PessoaPolicy;
use App\Policies\PontoAcessoPolicy;
use App\Policies\UserPolicy;
use App\Policies\VeiculoPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Serviços concretos são resolvidos automaticamente pelo container.
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Gate::policy(Acesso::class, AcessoPolicy::class);
        Gate::policy(Pessoa::class, PessoaPolicy::class);
        Gate::policy(Veiculo::class, VeiculoPolicy::class);
        Gate::policy(PontoAcesso::class, PontoAcessoPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ConfiguracaoSistema::class, ConfiguracaoSistemaPolicy::class);
        Gate::policy(LogAuditoria::class, LogAuditoriaPolicy::class);

        User::observe(AuditoriaObserver::class);
        Pessoa::observe(AuditoriaObserver::class);
        Veiculo::observe(AuditoriaObserver::class);
        PontoAcesso::observe(AuditoriaObserver::class);
        ConfiguracaoSistema::observe(AuditoriaObserver::class);
    }
}
