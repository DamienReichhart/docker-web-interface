<?php

namespace App\Entity\Form;

use App\Filters;

class DropDownModelAddField extends ModelAddField
{
    private array $options;
    final public function __construct(string $fieldName, array $options)
    {
        parent::__construct($fieldName);
        $this->options = $options;
    }

    /**
     * Render the HTML representation of the dropdown field.
     *
     * @param string|null $default
     * @return string
     */
    final public function render(string $default = null): string
    {
        $attributes = $this->renderAttributes();
        $label = Filters::collumnToName($this->label);
        $labelHtml = "<label for=\"$this->name\" class=\"form-label\">$label</label>";
        $inputHtml = "<select name=\"$this->name\" class=\"form-select\" id=\"$this->name\" $attributes>";

        // Determine the selected value
        $selectedValue = $default ?? $this->value;

        foreach ($this->options as $optionValue => $optionLabel) {
            $optionValueEscaped = htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8');
            $optionLabelEscaped = htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8');
            $selected = ($selectedValue !== null && $selectedValue === $optionValue) ? 'selected' : '';
            $inputHtml .= "<option value=\"$optionValueEscaped\" $selected>$optionLabelEscaped</option>";
        }
        $inputHtml .= "</select>";

        return "<div class=\"mb-3\">$labelHtml$inputHtml</div>";
    }


}