<?php

namespace BlitzPHP\RateLimiter\Strategies;

use BlitzPHP\Contracts\RateLimiter\Limiter;
use BlitzPHP\Contracts\RateLimiter\ResultInterface;
use BlitzPHP\RateLimiter\RateLimitResult;

/**
 * Stratégie Sliding Window - Plus équitable que Fixed Window
 */
class SlidingWindow extends BaseStrategy implements Limiter
{
    /**
     * {@inheritDoc}
     */
    public function attempt(string $key, int $limit, int $window, int $cost = 1): ResultInterface
    {
        $now = time();
        
        $data = $this->get($key, [
            'count'        => 0,
            'reset'        => $now + $window,
            'window_start' => $now,
        ]);
        
        $elapsed = $now - $data['window_start'];
        
        // Si la fenêtre est expirée, réinitialiser
        if ($elapsed >= $window) {
            $data = [
                'count'        => 0,
                'reset'        => $now + $window,
                'window_start' => $now,
            ];
        } else {
            // Calculer le poids de la fenêtre précédente
            $weight        = 1 - ($elapsed / $window);
            $data['count'] = (int) ceil($data['count'] * $weight);
        }
        
        $allowed = ($data['count'] + $cost) <= $limit;
        
        if ($allowed) {
            $data['count'] += $cost; 
			$this->set($key, $data, $window);
        }
        
        $remaining  = max(0, $limit - (int) $data['count']);
        $retryAfter = $allowed ? 0 : max(1, $data['reset'] - $now);
        
        return new RateLimitResult(
            allowed   : $allowed,
            limit     : $limit,
            remaining : $remaining,
            reset     : $data['reset'],
            retryAfter: $retryAfter
        );
    }
    
    /**
     * {@inheritDoc}
     */
    public function attempts(string $key): int
    {
        $data = $this->get($key);
        
        return $data ? (int) ($data['count'] ?? 0) : 0;
    }
	
	/**
     * {@inheritDoc}
     */
    public function increment(string $key, int $window, int $amount = 1): int
    {
        $now = time();
        
        $data = $this->get($key, [
            'count'        => 0,
            'reset'        => $now + $window,
            'window_start' => $now,
        ]);
        
        $elapsed = $now - $data['window_start'];
        
        // Si la fenêtre est expirée, réinitialiser
        if ($elapsed >= $window) {
            $data = [
                'count'        => 0,
                'reset'        => $now + $window,
                'window_start' => $now,
            ];
        } else {
            // Appliquer le poids de la fenêtre glissante
            $weight        = 1 - ($elapsed / $window);
            $data['count'] = (int) ceil($data['count'] * $weight);
        }
        
        $data['count'] += $amount;
        
        $data['reset'] = max($data['reset'], $now + $window);
        
        $this->set($key, $data, $window);
        
        return (int) $data['count'];
    }
    
    /**
     * {@inheritDoc}
     */
    public function availableIn(string $key): int
    {
        $data = $this->get($key);
        
        if (!$data) {
            return 0;
        }
        
        return max(0, ($data['reset'] ?? 0) - time());
    }
}
