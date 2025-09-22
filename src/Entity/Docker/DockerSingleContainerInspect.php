<?php /** @noinspection PhpUnused */

/** @noinspection DuplicatedCode */

namespace App\Entity\Docker;

use App\Helper\DockerHelper;
use JsonException;
use RuntimeException;
use stdClass;


class DockerSingleContainerInspect
{
    private stdClass $data;

    /**
     * @throws JsonException
     */
    public function __construct(string $id)
    {
        $output = DockerHelper::getInstance()->inspectContainer($id);
        $this->parse($output);
    }

    public function __get(string $name): string|stdClass|null|array
    {
        if (property_exists($this->data, $name)) {
            return $this->data->$name;
        }
        return null;
    }

    // Optional: Add the magic __isset method
    public function __isset(string $name): bool
    {
        return isset($this->data->$name);
    }

    public function __set(string $name, string $value): void
    {
        throw new RuntimeException('Cannot set properties on this object.');
    }

    /** @noinspection DuplicatedCode */
    /**
     * @throws JsonException
     */
    final public function parse(string $commandOutput): void
    {
        try {
            // Check if the output contains a permission denied error - this should be handled by DockerHelper now
            if (stripos($commandOutput, 'permission denied') !== false) {
                throw new RuntimeException('Permission denied while accessing Docker. Both regular and sudo attempts failed.');
            }
            
            // Check for other common error messages
            if (stripos($commandOutput, 'error') !== false && stripos($commandOutput, '{') === false) {
                throw new RuntimeException('Docker error: ' . trim($commandOutput));
            }
            
            $decodedData = json_decode($commandOutput, true, 512, JSON_THROW_ON_ERROR);
            
            if (empty($decodedData)) {
                // Handle empty response
                throw new RuntimeException('Empty response from Docker inspect command');
            }

            // If the data is an array with a single element, use that element.
            if (isset($decodedData[0]) && count($decodedData) === 1) {
                $decodedData = $decodedData[0];
            }

            $this->data = $this->assignProperties($decodedData);
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to parse Docker inspect output: ' . $e->getMessage() . '. Raw output: ' . substr($commandOutput, 0, 200));
        }
    }

    private function assignProperties(array $data): stdClass
    {
        $object = new stdClass();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Check if the array is associative or sequential.
                if ($this->isAssoc($value)) {
                    $object->$key = $this->assignProperties($value);
                } else {
                    $array = [];
                    foreach ($value as $index => $item) {
                        if (is_array($item)) {
                            $array[$index] = $this->assignProperties($item);
                        } else {
                            $array[$index] = $item;
                        }
                    }
                    $object->$key = $array;
                }
            } else {
                $object->$key = $value;
            }
        }

        return $object;
    }

    private function isAssoc(array $array): bool
    {
        if (array() === $array) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    final public function getConfig(): stdClass
    {
        return $this->data;
    }

    /**
     * @throws JsonException
     */
    final public function __toString(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }
}
