<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add wrestling-specific context to all requests.
 *
 * This middleware enriches the application context with information
 * relevant to wrestling management, providing better debugging
 * and audit trail capabilities for business operations.
 */
class AddWrestlingContext
{
    /**
     * Handle an incoming request and add wrestling-specific context.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Add basic request context
        Context::add('request_url', $request->url());
        Context::add('request_method', $request->method());
        Context::add('trace_id', Str::uuid()->toString());
        Context::add('timestamp', now()->toISOString());

        // Add user context if authenticated
        if ($user = $request->user()) {
            Context::add('user_id', $user->id);
            Context::add('user_name', $user->name);
            Context::add('user_email', $user->email);
            Context::addHidden('session_id', $request->session()->getId());
        }

        // Add wrestling-specific context
        Context::add('application_domain', 'wrestling_management');
        Context::add('system_version', config('app.version', '1.0.0'));

        // Add route-specific context for wrestling entities
        $this->addEntityContext($request);

        // Add IP and user agent for security context
        Context::addHidden('client_ip', $request->ip());
        Context::addHidden('user_agent', $request->userAgent());

        return $next($request);
    }

    /**
     * Add entity-specific context based on route parameters.
     */
    private function addEntityContext(Request $request): void
    {
        $route = $request->route();

        if (! $route) {
            return;
        }

        // Add wrestler context
        if ($wrestler = $route->parameter('wrestler')) {
            Context::add('wrestler_id', $wrestler->id ?? $wrestler);
            if (is_object($wrestler) && isset($wrestler->name)) {
                Context::add('wrestler_name', $wrestler->name);
                Context::add('wrestler_status', $wrestler->status ?? 'unknown');
            }
        }

        // Add manager context
        if ($manager = $route->parameter('manager')) {
            Context::add('manager_id', $manager->id ?? $manager);
            if (is_object($manager) && isset($manager->name)) {
                Context::add('manager_name', $manager->name);
            }
        }

        // Add tag team context
        if ($tagTeam = $route->parameter('tagTeam')) {
            Context::add('tag_team_id', $tagTeam->id ?? $tagTeam);
            if (is_object($tagTeam) && isset($tagTeam->name)) {
                Context::add('tag_team_name', $tagTeam->name);
            }
        }

        // Add event context
        if ($event = $route->parameter('event')) {
            Context::add('event_id', $event->id ?? $event);
            if (is_object($event) && isset($event->name)) {
                Context::add('event_name', $event->name);
                Context::add('event_date', $event->date?->toDateString());
            }
        }

        // Add match context
        if ($match = $route->parameter('match')) {
            Context::add('match_id', $match->id ?? $match);
        }

        // Add title context
        if ($title = $route->parameter('title')) {
            Context::add('title_id', $title->id ?? $title);
            if (is_object($title) && isset($title->name)) {
                Context::add('title_name', $title->name);
            }
        }
    }
}
