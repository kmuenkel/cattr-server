<?php

namespace App\Scopes;

use App\Enums\Role;
use App\Exceptions\Entities\AuthorizationException;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Throwable;

class ProjectAccessScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder
     * @throws Throwable
     */
    public function apply(Builder $builder, Model $model): Builder
    {
        if (!($user = auth()->user() ?? request()->user()) && app()->runningInConsole()) {
            return $builder;
        }

        throw_unless($user, new AuthorizationException);

        if ($user->hasRole([Role::ADMIN, Role::MANAGER, Role::AUDITOR])) {
            return $builder;
        }

        return $builder->whereHas('users', static fn(Builder $query) => $query->where('user_id', $user->id));
    }
}
