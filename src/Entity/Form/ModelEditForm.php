<?php

namespace App\Entity\Form;

use App\Filters;

class ModelEditForm extends ModelAddForm
{
    /**
     * Render the HTML form with all fields.
     *
     * @return string
     */
    private mixed $modelInstance = null;
    final public function renderForm(string $target): string
    {
        if ($this->modelInstance !== null) {
            $modelInstance = $this->modelInstance;
        }
        $modelName = substr(strrchr($this->model, "\\"), 1);
        $shownModelName = Filters::collumnToName($modelName);
        $formHtml = "<form method=\"POST\" action=\"$target\">" .
            "<h3 class=\"text-center text-secondary mb-4\">Modifier un $shownModelName</h3>";

        foreach ($this->fields as $field) {
            $fieldName = $field->getName();
            $defaultValue = null;

            if ($modelInstance !== null && isset($modelInstance->$fieldName)) {
                $defaultValue = $modelInstance->$fieldName;
            }
            $formHtml .= $field->render($defaultValue);
        }

        $formHtml .= '<button type="submit" class="btn btn-secondary w-100">Envoyer</button>';
        $formHtml .= '</form>';

        return $formHtml;
    }

    final public function setModelInstance(mixed $modelInstance): void
    {
        $this->modelInstance = $modelInstance;
    }

}