<?php /** @noinspection PhpUnused */

namespace App\Entity\Docker;
use App\Helper\DockerHelper;
use JsonException;
use RuntimeException;
use stdClass;

class DockerImageInspect
{
    private stdClass $data;

    /**
     * @throws JsonException
     */
    final public function __construct(string $id)
    {
        $output = DockerHelper::getInstance()->inspectImage($id);
        $this->parse($output);
    }

    final public function __get(string $name): string|array|stdClass|null
    {
        if (property_exists($this->data, $name)) {
            return $this->data->$name;
        }
        return null;
    }

    final public function __isset(string $name): bool
    {
        return isset($this->data->$name);
    }

    final public function __set(string $name, mixed $value): void
    {
        throw new RuntimeException('Cannot set properties on this object.');
    }

    /**
     * @throws JsonException
     */
    final public function parse(string $commandOutput): void
    {
        // Check if the output contains an error message
        if (str_starts_with(trim($commandOutput), 'Error response')) {
            throw new RuntimeException('Docker image inspection failed: ' . $commandOutput);
        }

        // Check if the output is empty or just contains []
        if (empty(trim($commandOutput)) || trim($commandOutput) === '[]') {
            throw new RuntimeException('No image data found. The image might not exist or be inaccessible.');
        }

        try {
            $decodedData = json_decode($commandOutput, true, 512, JSON_THROW_ON_ERROR);
            
            if (empty($decodedData)) {
                throw new RuntimeException('No image data found in the JSON response.');
            }

            $this->data = $this->assignProperties($decodedData[0]);
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to parse Docker image inspection data: ' . $e->getMessage());
        }
    }

    /** @noinspection DuplicatedCode */
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

}
