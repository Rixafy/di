<?php

/**
 * Test: AccessorDefinition API
 */

declare(strict_types=1);

use Nette\DI\Definitions\AccessorDefinition;
use Nette\DI\Definitions\Reference;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


interface Bad1
{
}

interface Bad2
{
	public function create();
}

interface Bad3
{
	public function get();

	public function foo();
}

interface Bad4
{
	public static function get();
}

interface Bad5
{
	public function get($arg);
}

interface Good1
{
	public function get();
}


Assert::exception(function () {
	$def = new AccessorDefinition;
	$def->setType('Foo');
}, Nette\MemberAccessException::class);


Assert::exception(function () {
	$def = new AccessorDefinition;
	$def->setImplement('Foo');
}, Nette\InvalidArgumentException::class, "[Service ?]
Interface 'Foo' not found.");


Assert::exception(function () {
	$def = new AccessorDefinition;
	$def->setImplement('stdClass');
}, Nette\InvalidArgumentException::class, "[Service ?]
Interface 'stdClass' not found.");


Assert::exception(function () {
	$def = new AccessorDefinition;
	$def->setImplement('Bad1');
}, Nette\InvalidArgumentException::class, '[Service ?]
Interface Bad1 must have just one non-static method get().');


Assert::exception(function () {
	$def = new AccessorDefinition;
	$def->setImplement('Bad2');
}, Nette\InvalidArgumentException::class, '[Service ?]
Interface Bad2 must have just one non-static method get().');


Assert::exception(function () {
	$def = new AccessorDefinition;
	$def->setImplement('Bad3');
}, Nette\InvalidArgumentException::class, '[Service ?]
Interface Bad3 must have just one non-static method get().');


Assert::exception(function () {
	$def = new AccessorDefinition;
	$def->setImplement('Bad4');
}, Nette\InvalidArgumentException::class, '[Service ?]
Interface Bad4 must have just one non-static method get().');


Assert::exception(function () {
	$def = new AccessorDefinition;
	$def->setImplement('Bad5');
}, Nette\InvalidArgumentException::class, '[Service ?]
Method Bad5::get() must have no parameters.');


Assert::noError(function () {
	$def = new AccessorDefinition;
	$def->setImplement('Good1');
	Assert::same('Good1', $def->getImplement());
	Assert::same('Good1', $def->getType());
});


test('', function () {
	$def = new AccessorDefinition;
	$def->setImplement('Good1');

	$def->setReference('stdClass');
	Assert::equal(new Reference('\stdClass'), $def->getReference());

	$def->setReference('@one');
	Assert::equal(new Reference('one'), $def->getReference());
});
