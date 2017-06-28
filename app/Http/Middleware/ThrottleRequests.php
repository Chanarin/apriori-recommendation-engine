<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class ThrottleRequests
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new request throttler.
     *
     * @param \Illuminate\Cache\RateLimiter $limiter
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param int                      $maxAttempts
     * @param int                      $decayMinutes
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            return $this->buildResponse($key, $maxAttempts);
        }
        $this->limiter->hit($key, $decayMinutes);
        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string
     */
    protected function resolveRequestSignature(Request $request)
    {
        $parts = $this->parts($request);
        
        if (!is_null($request->get('access_token'))) {
            return sha1(
                $parts.'|'.$request->access_token
            );
        }

        if (!is_null($request->get('client_id'))) {
            return sha1(
                $parts.'|'.$request->client_id
            );
        }
        
        return sha1(
            $parts.'|'.$request->ip()
        );
    }
    
    private function parts(Request $request)
    {
        return $request->method().'|'.$request->server('SERVER_NAME').'|'.$request->path();
    }
    
    /**
     * Create a 'too many attempts' response.
     *
     * @param string $key
     * @param int    $maxAttempts
     *
     * @return \Illuminate\Http\Response
     */
    protected function buildResponse($key, $maxAttempts)
    {
        $response = new Response('Too Many Attempts.', 429);
        $retryAfter = $this->limiter->availableIn($key);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );
    }

    /**
     * Add the limit header information to the given response.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param int                                        $maxAttempts
     * @param int                                        $remainingAttempts
     * @param int|null                                   $retryAfter
     *
     * @return \Illuminate\Http\Response
     */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $headers = [
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];
        if (!is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
        }
        $response->headers->add($headers);

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param string   $key
     * @param int      $maxAttempts
     * @param int|null $retryAfter
     *
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        if (!is_null($retryAfter)) {
            return 0;
        }

        return $this->limiter->retriesLeft($key, $maxAttempts);
    }
}
