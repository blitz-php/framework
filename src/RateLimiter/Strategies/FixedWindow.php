<?php

namespace BlitzPHP\RateLimiter\Strategies;

use BlitzPHP\Contracts\RateLimiter\Limiter;
use BlitzPHP\Contracts\RateLimiter\ResultInterface;
use BlitzPHP\RateLimiter\RateLimitResult;

/**
 * Stratégie Fixed Window - Simple et efficace
 */
class FixedWindow extends BaseStrategy implements Limiter
{
    /**
     * {@inheritDoc}
     */
    public function attempt(string $key, int $limit, int $window, int $cost = 1): ResultInterface
    {
        $now          = time();
        $windowStart  = (int) ($now / $window) * $window;
        $key         .= ':' . $windowStart;
        
        $count = (int) $this->get($key, 0);
        $allowed = ($count + $cost) <= $limit;
        
        if ($allowed) {
            $count += $cost;
            $ttl = $windowStart + $window - $now;
            $this->set($key, $count, max(1, $ttl));
        }
        
        $remaining  = max(0, $limit - $count);
        $reset      = $windowStart + $window;
        $retryAfter = $allowed ? 0 : max(1, $reset - $now);
        
        return new RateLimitResult(
            allowed   : $allowed,
            limit     : $limit,
            remaining : $remaining,
            reset     : $reset,
            retryAfter: $retryAfter
        );
    }
    
    /**
     * {@inheritDoc}
     */
    public function attempts(string $key): int
    {
        // On ne peut pas savoir la fenêtre exacte sans la stocker
        // On fait donc un best-effort sur les fenêtres courantes
        $now    = time();
        $total  = 0;
        
        // Vérifier les fenêtres possibles (1min, 5min, 15min, 1h, 24h)
        $windows = [60, 300, 900, 3600, 86400];
        
        foreach ($windows as $window) {
            $windowStart = (int) ($now / $window) * $window;
            $count       = (int) $this->get($key . ':' . $windowStart, 0);
            $total       = max($total, $count);
        }
        
        return $total;
    }
    
    /**
     * {@inheritDoc}
     */
    public function increment(string $key, int $window, int $amount = 1): int
    {
        $now         = time();
        $windowStart = (int) ($now / $window) * $window;
        $windowKey   = $key . ':' . $windowStart;
        
        $count = (int) $this->get($windowKey, 0);
        
        $count += $amount;
        
        $ttl = $windowStart + $window - $now;
        
        $this->set($windowKey, $count, max(1, $ttl));
        
        return $count;
    }
    
    /**
     * {@inheritDoc}
     */
    public function availableIn(string $key): int
    {
        $now = time();
        
		// Chercher parmi les fenêtres communes
        $windows = [60, 300, 900, 3600, 86400];
        
        foreach ($windows as $window) {
            $windowStart = (int) ($now / $window) * $window;
            $windowKey   = $key . ':' . $windowStart;
            
            if ($this->cache->has($this->prefix . $windowKey)) {
                $reset = $windowStart + $window;
                if ($reset > $now) {
                    return $reset - $now;
                }
            }
        }
        
        return 0;
    }
    
    /**
     * {@inheritDoc}
     */
    public function reset(string $key): bool
    {
        $now    = time();
        $windows = [60, 300, 900, 3600, 86400];
        $deleted = false;
        
        // Réinitialiser toutes les fenêtres possibles
        foreach ($windows as $window) {
            $windowStart = (int) ($now / $window) * $window;
            $windowKey   = $key . ':' . $windowStart;
            
            if (parent::reset($windowKey)) {
                $deleted = true;
            }
        }
        
        return $deleted;
	}
}
