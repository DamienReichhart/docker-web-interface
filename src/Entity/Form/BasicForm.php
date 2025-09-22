<?php

namespace App\Entity\Form;

class BasicForm {
    private array $fields = array();
    private string $title;
    private string $action;

    final public function __construct(string $title, string $action)
    {
        $this->title = $title;
        $this->action = $action;
    }

    final public function addLine(string $label, string $name, string $type = 'text', string|array $value = '', string $placeholder = ''): void
    {
        $this->fields[] = array(
            'label' => $label,
            'name'  => $name,
            'type'  => $type,
            'value' => $value,
            'placeholder' => $placeholder
        );
    }

    final public function __toString() : string
    {
        $html = "<form action='$this->action' method='POST'>";
        $html .= '<h2>' . htmlspecialchars($this->title) . '</h2>';
        foreach ($this->fields as $field) {
            $html .= '<div class="form-group mb-3">';
            if (!($field['type'] === 'htmlComment')){
                $html .= '<label for="' . htmlspecialchars($field['name']) . '" class="form-label">' . htmlspecialchars($field['label']) . '</label>';
            }
            
            if ($field['type'] === 'htmlComment') {
                $html .= "<div class=''><a href=". $field["value"]["href"] . '>' . $field["value"]["comment"] ."</a></div>";
            } elseif ($field['type'] === 'select') {
                $html .= '<select name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '" class="form-select">';
                if (is_array($field['value'])) {
                    foreach ($field['value'] as $key => $option) {
                        $html .= '<option value="' . htmlspecialchars($key) . '">' . htmlspecialchars($option) . '</option>';
                    }
                }
                $html .= '</select>';
            } elseif ($field['type'] === 'textarea') {
                $html .= '<textarea name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '" class="form-control"';
                if (!empty($field['placeholder'])) {
                    $html .= ' placeholder="' . htmlspecialchars($field['placeholder']) . '"';
                }
                $html .= '>' . htmlspecialchars($field['value']) . '</textarea>';
            } else {
                $value = is_array($field['value']) ? implode(', ', $field['value']) : $field['value'];
                $html .= '<input type="' . htmlspecialchars($field['type']) . '" name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '" value="' . htmlspecialchars($value) . '" class="form-control"';
                if (!empty($field['placeholder'])) {
                    $html .= ' placeholder="' . htmlspecialchars($field['placeholder']) . '"';
                }
                $html .= '>';
            }
            
            $html .= '</div>';
        }
        $html .= '<button type="submit" class="btn btn-primary">Enregistrer</button>';
        $html .= '<a href="javascript:history.back()" class="btn btn-secondary ms-2">Annuler</a>';
        $html .= '</form>';
        return $html;
    }
}

