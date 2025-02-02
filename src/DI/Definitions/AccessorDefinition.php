<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\DI\Definitions;

use Nette;
use Nette\DI\ServiceCreationException;
use Nette\Utils\Reflection;


/**
 * Accessor definition.
 */
final class AccessorDefinition extends Definition
{
	private const METHOD_GET = 'get';

	private ?Reference $reference = null;


	public function setImplement(string $type): static
	{
		if (!interface_exists($type)) {
			throw new Nette\InvalidArgumentException(sprintf(
				"[%s]\nInterface '%s' not found.",
				$this->getDescriptor(),
				$type,
			));
		}
		$rc = new \ReflectionClass($type);

		$method = $rc->getMethods()[0] ?? null;
		if (
			!$method
			|| $method->isStatic()
			|| $method->getName() !== self::METHOD_GET
			|| count($rc->getMethods()) > 1
		) {
			throw new Nette\InvalidArgumentException(sprintf(
				"[%s]\nInterface %s must have just one non-static method get().",
				$this->getDescriptor(),
				$type,
			));
		} elseif ($method->getNumberOfParameters()) {
			throw new Nette\InvalidArgumentException(sprintf(
				"[%s]\nMethod %s::get() must have no parameters.",
				$this->getDescriptor(),
				$type,
			));
		}
		return parent::setType($type);
	}


	public function getImplement(): ?string
	{
		return $this->getType();
	}


	public function setReference(string|Reference $reference): static
	{
		if ($reference instanceof Reference) {
			$this->reference = $reference;
		} else {
			$this->reference = str_starts_with($reference, '@')
				? new Reference(substr($reference, 1))
				: Reference::fromType($reference);
		}
		return $this;
	}


	public function getReference(): ?Reference
	{
		return $this->reference;
	}


	public function resolveType(Nette\DI\Resolver $resolver): void
	{
	}


	public function complete(Nette\DI\Resolver $resolver): void
	{
		if (!$this->reference) {
			$interface = $this->getType();
			$method = new \ReflectionMethod($interface, self::METHOD_GET);
			$returnType = Nette\DI\Helpers::getReturnType($method);

			if (!$returnType) {
				throw new ServiceCreationException(sprintf('Method %s::get() has no return type or annotation @return.', $interface));
			} elseif (!class_exists($returnType) && !interface_exists($returnType)) {
				throw new ServiceCreationException(sprintf(
					"Class '%s' not found.\nCheck the return type or annotation @return of the %s::get() method.",
					$returnType,
					$interface,
				));
			}
			$this->setReference($returnType);
		}

		$this->reference = $resolver->normalizeReference($this->reference);
	}


	public function generateMethod(Nette\PhpGenerator\Method $method, Nette\DI\PhpGenerator $generator): void
	{
		$class = (new Nette\PhpGenerator\ClassType)
			->addImplement($this->getType());

		$class->addProperty('container')
			->setPrivate();

		$class->addMethod('__construct')
			->addBody('$this->container = $container;')
			->addParameter('container')
			->setType($generator->getClassName());

		$rm = new \ReflectionMethod($this->getType(), self::METHOD_GET);

		$class->addMethod(self::METHOD_GET)
			->setBody('return $this->container->getService(?);', [$this->reference->getValue()])
			->setReturnType(Reflection::getReturnType($rm));

		$method->setBody('return new class ($this) ' . $class . ';');
	}
}
