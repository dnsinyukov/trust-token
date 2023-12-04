<?php

namespace Coderden\TrustToken;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class TokenRepository implements TokenRepositoryInterface
{
    /**
     * The database connection instance.
     *
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * The token database table.
     *
     * @var string
     */
    protected $table;

    /**
     * The hashing key.
     *
     * @var string
     */
    protected $hashKey;

    /**
     * @var string
     */
    protected string $cipher = 'aes-256-cbc';


    /**
     * Create a new token repository instance.
     *
     * @param ConnectionInterface $connection
     * @param string $table
     * @param string $hashKey
     */
    public function __construct(
        ConnectionInterface $connection,
        string $table,
        string $hashKey
    ) {
        $this->hashKey = $hashKey;
        $this->table = $table;
        $this->connection = $connection;
    }

    /**
     * Create a new token record.
     *
     * @param $email $user
     * @return string
     *
     * @throws \Exception
     */
    public function createToken(
        string $name,
        int $userId,
        array $scopes = [],
        \DateTimeInterface $expiresAt = null
    ): AccessToken {
        $token = $this->createNewToken();

        $payload = [
            'name' => $name,
            'user_id' => $userId,
            'token' => $this->encode($token),
            'expires_at' => $expiresAt,
            'revoked' => false,
            'scopes' => implode(' ', $scopes),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload['id'] = $this->query()->insertGetId($payload);

        return new AccessToken($payload, $token);
    }

    /**
     * @param string $token
     * @return Model|Builder|object|null
     * @throws \Exception
     */
    public function findByToken(string $token)
    {
        return $this->query()->where('token', $this->encode($token))->first();
    }

    /**
     * @throws \Exception
     */
    public function createNewToken(): string
    {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));

        $key = Str::random(16);

        return base64_encode($key . '::' . $iv);
    }

    /**
     * @param string $token
     * @return string
     */
    public function decode(string $token): string
    {
        try {
            [$value, $iv] = explode('::', base64_decode($token));

            return openssl_decrypt($value, $this->cipher, $this->hashKey, OPENSSL_RAW_DATA, $iv);
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * @param string $token
     * @return string
     * @throws \Exception
     */
    public function encode(string $token): string
    {
        try {
            [, $iv] = explode('::', base64_decode($token));

            $value = \openssl_encrypt($token, $this->cipher, $this->hashKey, OPENSSL_RAW_DATA, $iv);
        } catch (\Exception $exception) {
            return '';
        }

        return base64_encode($value . '::' . $iv);
    }

    /**
     * @return Builder
     */
    protected function query(): Builder
    {
        return $this->connection->table($this->table);
    }
}
