<?php

namespace Coderden\TrustToken;

use Illuminate\Contracts\Auth\{
    Authenticatable,
    Factory,
    UserProvider
};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class Guard
{
    /**
     * @var Factory
     */
    protected $auth;


    /**
     * @var int|null
     */
    protected $expiration;

    /**
     * Create a new guard instance.
     *
     * @param Factory $auth
     */
    public function __construct(Factory $auth, int $expiration = null)
    {
        $this->auth = $auth;
        $this->expiration = $expiration;
    }
    /**
     * @param Request $request
     * @param UserProvider $provider
     * @return Authenticatable|null
     */
    public function __invoke(Request $request, UserProvider $provider)
    {
        $bearerToken = $request->bearerToken();

        if (empty($bearerToken)) {
            return null;
        }

        $accessToken =  app(TokenRepositoryInterface::class)->findByToken($bearerToken);

        if (! $this->isValidAccessToken($accessToken)) {
            return null;
        }

        $userId = $accessToken->user_id;

        return $provider->retrieveById($userId);
    }


    /**
     * @param $accessToken
     * @return bool
     */
    protected function isValidAccessToken($accessToken): bool
    {
        if (! $accessToken) {
            return false;
        }

        $expiresAt = null;

        if (isset($accessToken->expires_at)) {
            $expiresAt = Carbon::parse($accessToken->expires_at);
        } elseif (isset($this->expiration)) {
            $expiresAt = Carbon::parse($accessToken->created_at)->addMinutes($this->expiration);
        }

        if (isset($expiresAt) && $expiresAt->isPast()) {
            return false;
        }

        return true;
    }
}
