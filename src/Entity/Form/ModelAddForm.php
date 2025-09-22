<?php

namespace App\Entity\Form;

use App\Filters;
use Exception;
use RuntimeException;

class ModelAddForm
{
    private static array $exceptions = ['id', 'urlIdentifierServer', 'idUsers'];
    protected array $fields = [];
    protected string $model;

    // In App\Entity\Form\ModelAddForm

    /**
     * @throws Exception
     */
    final public function __construct(string $model)
    {
        $this->model = $model;

        if (class_exists($this->model)) {
            if (property_exists($this->model, 'columns')) {
                foreach ($this->model::$columns as $element) {
                    if (!in_array($element, $this::$exceptions, true)){
                        if ($this->model::$foreignKeys[$element] ?? false) {
                            $foreignModelClass = $this->model::$foreignKeys[$element];
                            $records = $foreignModelClass::all();
                            $options = [];
                            foreach ($records as $record) {
                                $options[$record->id] = $record->getDisplayName();
                            }
                            $this->fields[] = new DropDownModelAddField($element, $options);
                        } else {
                            $this->fields[] = new ModelAddField($element);
                        }
                    }
                }
            } else {
                throw new RuntimeException("La propriété statique 'columns' n'existe pas dans la classe $this->model");
            }
        } else {
            throw new RuntimeException("La classe $this->model n'existe pas");
        }
    }


    /**
     * Render the HTML form with all fields.
     *
     * @param string $target
     * @return string
     * @noinspection MethodShouldBeFinalInspection
     */
    public function renderForm(string $target): string
    {
        $modelName = substr(strrchr($this->model, "\\"), 1);
        $shownModelName = Filters::collumnToName($modelName);
        $formHtml = "<form method=\"POST\" action=\"$target\">" .
            "<h3 class=\"text-center text-secondary mb-4\">Ajouter un $shownModelName</h3>";

        foreach ($this->fields as $field) {
            $defaultValue = null;
            $formHtml .= $field->render($defaultValue);
        }

        $formHtml .= '<button type="submit" class="btn btn-secondary w-100">Envoyer</button>';
        $formHtml .= '</form>';

        return $formHtml;
    }

}

