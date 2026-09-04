<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enregistre une vue anonyme sur le modèle résolu par la route courante
 * (ex. {article} dans /blog/{article:slug}), pour alimenter les statistiques
 * de fréquentation du dashboard admin.
 *
 * Dédoublonnage : une seule vue comptée par IP (hashée) et par contenu, sur
 * une fenêtre d'1 heure â€” évite qu'un rechargement de page ou une navigation
 * aller-retour ne gonfle artificiellement les statistiques.
 *
 * Vie privée : l'IP n'est jamais stockée en clair, uniquement son hash SHA-256
 * combiné à APP_KEY (empêche la ré-identification par simple recherche inverse
 * de hash, et rend le hash différent d'une installation Laravel à l'autre).
 *
 * Usage : middleware('track.view:article') sur une route dont le premier
 * paramètre résolu est le modèle à tracker (route model binding).
 */
class TrackPageView
{
    private const DEDUP_WINDOW_MINUTES = 60;

    public function handle(Request $request, Closure $next, string $routeParam): Response
    {
        $response = $next($request);

        // On ne compte que les pages effectivement servies avec succès
        // (pas les 404, pas les erreurs serveur).
        if (!$response->isSuccessful()) {
            return $response;
        }

        $model = $request->route($routeParam);

        if ($model instanceof Model && method_exists($model, 'pageViews')) {
            $this->recordView($model, $request);
        }

        return $response;
    }

    private function recordView(Model $model, Request $request): void
    {
        $ipHash = hash('sha256', $request->ip() . config('app.key'));

        $alreadyViewed = PageView::query()
            ->where('viewable_type', $model->getMorphClass())
            ->where('viewable_id', $model->getKey())
            ->where('ip_hash', $ipHash)
            ->where('viewed_at', '>=', now()->subMinutes(self::DEDUP_WINDOW_MINUTES))
            ->exists();

        if ($alreadyViewed) {
            return;
        }

        PageView::create([
            'viewable_type' => $model->getMorphClass(),
            'viewable_id' => $model->getKey(),
            'ip_hash' => $ipHash,
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'viewed_at' => now(),
        ]);
    }
}