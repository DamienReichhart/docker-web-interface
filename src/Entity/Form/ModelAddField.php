<?php

namespace App\Entity\Form;

use App\Filters;

class ModelAddField
{
    protected string $name;
    protected string $type;
    protected string $label;
    protected ?string $value;
    private array $attributes;

    /**
     * Initialize the field with the given name.
     *
     * @param string $fieldName
     */
    public function __construct(string $fieldName)
    {
        $this->name = $fieldName;
        $this->type = $this->inferFieldType($fieldName);
        $this->label = ucfirst(str_replace('_', ' ', $this->name));
        $this->value = '';
        $this->attributes = [];
    }

    /**
     * Infer the field type based on the field name.
     *
     * @param string $fieldName
     * @return string
     */
    private function inferFieldType(string $fieldName): string
    {
        // Simple inference based on field name
        if (stripos($fieldName, 'email') !== false) {
            return 'email';
        }

        if (stripos($fieldName, 'password') !== false) {
            return 'password';
        }

        if (stripos($fieldName, 'date') !== false) {
            return 'date';
        }

        if (stripos($fieldName, 'description') !== false || stripos($fieldName, 'content') !== false || stripos($fieldName, 'bio') !== false) {
            return 'textarea';
        }

        return 'text';
    }

    /**
     * Render the HTML representation of the field.
     *
     * @param string|null $default
     * @return string
     * @noinspection MethodShouldBeFinalInspection
     */
    public function render(string $default = null): string
    {
        $attributes = $this->renderAttributes();
        $label = Filters::collumnToName($this->label);
        $labelHtml = "<label for=\"$this->name\" class=\"form-label\">$label</label>";

        // Use the default value if provided; otherwise, use the existing value
        $value = $default ?? $this->value;
        $valueEscaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        if ($this->type === 'textarea') {
            $inputHtml = "<textarea name=\"$this->name\" class=\"form-control\" id=\"$this->name\" $attributes>$valueEscaped</textarea>";
        } else {
            $inputHtml = "<input type=\"$this->type\" class=\"form-control\" name=\"$this->name\" id=\"$this->name\" value=\"$valueEscaped\" $attributes>";
        }

        return "<div class=\"mb-3\">$labelHtml$inputHtml</div>";
    }

    /**
     * Convert attributes array to string for HTML output.
     *
     * @return string
     * @noinspection MethodVisibilityInspection
     */
    final protected function renderAttributes(): string
    {
        $attrStrings = [];

        foreach ($this->attributes as $key => $value) {
            $attrStrings[] = "$key=\"$value\"";
        }

        return implode(' ', $attrStrings);
    }

    final public function getName(): string
    {
        return $this->name;
    }
}
