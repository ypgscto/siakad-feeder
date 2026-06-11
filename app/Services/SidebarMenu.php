<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class SidebarMenu
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $items = [];

        foreach (config('sifeeder_sidebar.items', []) as $item) {
            if (! $this->userCanSeeItem($user, $item['roles'] ?? [])) {
                continue;
            }

            $items[] = $this->buildLink($item);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function buildLink(array $item): array
    {
        $routeName = (string) ($item['route'] ?? 'dashboard');
        $activeRoutes = $item['active_routes'] ?? [$routeName, $routeName.'.*'];
        $active = false;

        foreach ($activeRoutes as $pattern) {
            if (request()->routeIs($pattern)) {
                $active = true;
                break;
            }
        }

        return [
            'type' => 'link',
            'label' => (string) ($item['label'] ?? ''),
            'route' => $routeName,
            'icon' => (string) ($item['icon'] ?? 'link'),
            'url' => Route::has($routeName) ? route($routeName) : route('dashboard'),
            'active' => $active,
        ];
    }

    /**
     * @param  list<string>  $allowedRoles
     */
    protected function userCanSeeItem(User $user, array $allowedRoles): bool
    {
        if (in_array('*', $allowedRoles, true)) {
            return true;
        }

        return in_array($user->role, $allowedRoles, true);
    }
}
