<?php

namespace Illuminate3\Crud\Subscriber;

use Illuminate\Events\Dispatcher as Events;
use Illuminate3\Crud\CrudController;
use Illuminate3\Form\Element;
use Illuminate3\Form\Element\ElementInterface;
use Illuminate3\Form\Element\Type;
use Illuminate3\Form\Element\Type\MultipleChoice;
use Illuminate3\Model\ModelBuilder;

class BuildModelWhenFormIsReady
{
	/**
	 *
	 * Let's have the ModelBuilder interact with the FormBuilder.
	 *
	 * @param Events $events
	 */
	public function subscribe(Events $events)
	{
		$events->listen('crudController.init', array($this, 'buildModel'));
	}

	/**
	 * @param CrudController $controller
	 */
	public function buildModel(CrudController $controller)
	{
		$mb = $controller->getModelBuilder();
		foreach($controller->getFormBuilder()->getElements() as $element) {
			$this->buildFormElement($element, $mb);
		}
	}

	/**
	 * @param ElementInterface $element
	 * @param ModelBuilder $mb
	 */
	public function buildFormElement(ElementInterface $element, ModelBuilder $mb)
	{
		// Only continue if the element has to be mapped to a model.
		if ($element instanceof MultipleChoice && !$element->isMapped()) {
			return;
		}

		$name = $element->getName();

		switch ($element) {

			case ($element instanceof Element\Text):
				$mb->column($name)->type('string');
				break;

			case ($element instanceof Element\Textarea):
				$mb->column($name)->type('text');
				break;

			case ($element->hasRule('integer')):
				$mb->column($name)->type('integer');
				break;

			case (($element instanceof Element\ModelElement) && ($element instanceof Type\Choice)):
				$mb->column($name)->type('integer');
				break;

			default:
				$mb->column($name)->type('string');
		}

		if ($element->getRules()) {
			$mb->get($name)->validate($element->getRules());
		}
	}

}