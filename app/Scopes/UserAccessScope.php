<?php

namespace App\Scopes;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Throwable;

class UserAccessScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder|null
     * @throws Throwable
     */
    public function apply(Builder $builder, Model $model): ?Builder
    {
        if (!($user = auth()->user() ?? request()->user())) {
            return app()->runningInConsole() ? $builder : null;
        }

        if ($user->hasRole([Role::ADMIN, Role::MANAGER, Role::AUDITOR])) {
            return $builder;
        }

        return $builder
            ->where('id', $user->id)
            ->orWhereHas('projectsRelation', static fn (Builder $builder) => $builder
                ->whereIn('project_id', static fn (QueryBuilder $builder) => $builder
                    ->from('projects_users')
                    ->select('project_id')
                    ->where('user_id', $user->id)
                    ->whereIn('role_id', [Role::ADMIN->value, Role::MANAGER->value, Role::AUDITOR->value])));
    }
}
