<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener;

class QueueName
{
    /**
     * @param string $prefix
     * @param array $events
     * @return string
     */
    public static function resolve(string $prefix, array $events): string
    {
        $name = $prefix . ':' . implode(',', $events);

        if (strlen($name) > 200) {
            $name = $prefix . ':' . md5(implode(',', $events));
        }

        return $name;
    }
}
