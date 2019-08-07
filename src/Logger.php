<?php

declare(strict_types=1);

/**
 * Copyright (C) 2019 PRONOVIX GROUP BVBA.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *  *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *  *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,
 * USA.
 */

namespace Pronovix\ComposerLogger;

use Composer\IO\IOInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * PSR-3 logger wrapper around IOInterface.
 */
final class Logger extends AbstractLogger
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * @var string
     */
    private $name;

    /**
     * Logger constructor.
     *
     * @param string $channel
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(string $channel, IOInterface $io)
    {
        $this->io = $io;
        $this->name = $channel;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = []): void
    {
        switch ($level) {
            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
            case LogLevel::EMERGENCY:
                $this->io->writeError(['<error>' . $this->buildMessage($message, $context) . '</error>']);
                break;
          case LogLevel::WARNING:
              $this->io->writeError(['<fg=yellow>' . $this->buildMessage($message, $context) . '</>']);
            break;
          case LogLevel::DEBUG:
                if ($this->io->isDebug()) {
                    $this->io->write(['<info>' . $this->buildMessage($message, $context) . '</info>']);
                }
                break;
            default:
                if ($this->io->isVerbose()) {
                    $this->io->write(['<info>' . $this->buildMessage($message, $context) . '</info>']);
                }
        }
    }

    /**
     * Interpolates context values into the message placeholders and prefixes the message with the plugin's name.
     *
     * @param string $message
     * @param array $context
     *
     * @return string
     *
     * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#12-message
     * @see https://github.com/symfony/console/blob/3.4/Logger/ConsoleLogger.php#L108-L128
     */
    private function buildMessage(string $message, array $context): string
    {
        if (false === strpos($message, '{')) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
            } elseif (\is_object($val)) {
                $replacements["{{$key}}"] = '[object ' . \get_class($val) . ']';
            } else {
                $replacements["{{$key}}"] = '[' . \gettype($val) . ']';
            }
        }

        return $this->name . ': ' . strtr($message, $replacements);
    }
}
