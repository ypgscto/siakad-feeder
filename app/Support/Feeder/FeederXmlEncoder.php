<?php

namespace App\Support\Feeder;

use SimpleXMLElement;

final class FeederXmlEncoder
{
    /**
     * @param  array<string, mixed>|\Traversable<string, mixed>  $data
     */
    public static function encode(array|\Traversable $data): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
        self::append($data, $xml);

        return $xml->asXML() ?: '<data></data>';
    }

    /**
     * @param  array<string, mixed>|\Traversable<string, mixed>  $data
     */
    protected static function append(array|\Traversable $data, SimpleXMLElement $node): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $node->addChild((string) $key);
                self::append($value, $child);
                continue;
            }

            $node->addChild(
                (string) $key,
                $value === null || $value === '' ? '' : (string) $value,
            );
        }
    }
}
