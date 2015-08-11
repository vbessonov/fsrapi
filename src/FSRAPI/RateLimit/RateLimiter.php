<?php
/*
 * The MIT License
 *
 * Copyright 2015 Vyacheslav Bessonov <v.bessonov@hotmail.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace VBessonov\FSRAPI\RateLimit;

/**
 * Description of RateLimiter
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class RateLimiter
{
    private $maxLimit = 10;
    private $regenerationTime = 60;
    private $regenerationAmount = 10;

    public function __construct(RateLimitStorageInterface $storage, $config = array())
    {
        $this->storage = $storage;
    }

    public function getMaxLimit()
    {
        return $this->maxLimit;
    }

    public function getCurrentAmount($id)
    {
        $currentLimitInfo = $this->storage->get($id);

        if (false === $currentLimitInfo) {
            return $this->maxLimit;
        }

        list($previousTime, $previousAmount) = explode('|', $currentLimitInfo, 2);
        $additionalAmount = $this->calculateAdditionalAmount($previousTime);
        $currentAmount = intval($previousAmount + $additionalAmount);

        return min($currentAmount, $this->maxLimit);
    }

    public function hasReachedLimit($id)
    {
        $currentAmount = $this->getCurrentAmount($id);

        return $currentAmount == 0;
    }

    public function hasReachedCertainLimit($id, $amount)
    {
        $currentLimit = $this->getCurrentAmount($id);

        return $currentLimit < $amount;
    }

    public function set($id, $amount)
    {
        $amountInfo = implode('|', array(time(), $amount));
        
        return $this->storage->set($id, $amountInfo);
    }

    public function substract($id, $amount)
    {
        $currentAmount = $this->getCurrentAmount($id);
        $newAmount = max(0, $currentAmount - $amount);

        return $this->set($id, $newAmount);
    }

    private function calculateAdditionalAmount($previousTime)
    {
        $additionalAmount = ((time() - $previousTime) / $this->regenerationTime) * $this->regenerationAmount;

        return round($additionalAmount);
    }
}