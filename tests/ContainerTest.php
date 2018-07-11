<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2018/7/7 17:29
 */

namespace Flashytime\Container\Tests;

use Flashytime\Container\Container;
use Flashytime\Container\NotFoundException;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    public $container;

    public function setUp()
    {
        $this->container = new Container();
    }

    public function testClosure()
    {
        //hello world
        $this->container->set('hello', function () {
            return 'Hello World!';
        });
        $this->assertSame('Hello World!', $this->container->get('hello'));

        //container passes self to Closure
        $this->container->set('name', function () {
            return 'Mocha';
        });
        $this->container->set('mocha', function ($container) {
            return sprintf('Hello %s!', $container->get('name'));
        });
        $this->assertSame('Hello Mocha!', $this->container->get('mocha'));
    }

    public function testInstanceOf()
    {
        //foo instanceof Foo
        $this->container->set('foo', 'Flashytime\Container\Tests\Foo');
        $this->assertTrue($this->container->has('foo'));
        $foo = $this->container->get('foo');
        $this->assertTrue($foo instanceof Foo);
        $this->assertInstanceOf(Foo::class, $foo);

        //foo2 instanceof Foo
        $this->container->set('foo2', function () {
            return new Foo();
        });
        $foo2 = $this->container->get('foo2');
        $this->assertInstanceOf(Foo::class, $foo2);

        //foo3 instanceof Foo
        $this->container->set('foo3', new Foo());
        $foo3 = $this->container->get('foo3');
        $this->assertInstanceOf(Foo::class, $foo3);

        //Foo instanceof FooInterface
        $this->container->set(FooInterface::class, Foo::class);
        $this->assertInstanceOf(FooInterface::class, $this->container->get(FooInterface::class));
        $this->assertInstanceOf(Foo::class, $this->container->get(FooInterface::class));
    }

    public function testWithParameters()
    {
        //closure with one parameter
        $this->container->set('one', function ($container, $name) {
            return sprintf('Hello %s!', $name);
        });
        $mocha = $this->container->get('one', ['Mocha']);
        $this->assertSame('Hello Mocha!', $mocha);

        //closure with two parameters
        $this->container->set('two', function ($container, $name, $age) {
            return sprintf('My name is %s, I am %d.', $name, $age);
        });
        $mocha = $this->container->get('two', ['Mocha', 18]);
        $this->assertSame('My name is Mocha, I am 18.', $mocha);

        //class with parameters
        $this->container->set('foo', Foo::class);
        $foo = $this->container->get('foo', ['Mocha', 18]);
        $this->assertSame('Mocha', $foo->getName());
        $this->assertSame(18, $foo->getAge());
    }

    public function testSingleton()
    {
        //singleton
        $this->container->setSingleton('foo', Foo::class);
        $first = $this->container->get('foo');
        $second = $this->container->get('foo');
        $this->assertSame($first, $second);
        $this->assertTrue($first === $second);

        //not singleton
        $this->container->set('foo', Foo::class);
        $first = $this->container->get('foo');
        $second = $this->container->get('foo');
        $this->assertNotSame($first, $second);
        $this->assertFalse($first === $second);
    }

    public function testDependencyInjection()
    {
        $this->container->set(FooInterface::class, Foo::class);
        $this->container->set('bar', Bar::class);
        $bar = $this->container->get('bar');
        $this->assertInstanceOf(Bar::class, $bar);
        $this->assertInstanceOf(FooInterface::class, $bar->getFoo());
        $this->assertInstanceOf(Foo::class, $bar->getFoo());
    }

    public function testNotFound()
    {
        $this->expectException(NotFoundException::class);
        $this->container->get('unknown');
    }
}

//tests classes
interface FooInterface
{

}

class Foo implements FooInterface
{
    private $name;
    private $age;

    public function __construct($name = null, $age = 0)
    {
        $this->name = $name;
        $this->age = $age;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setAge($age)
    {
        $this->age = $age;
    }

    public function getAge()
    {
        return $this->age;
    }
}

class Bar
{
    public $foo;

    public function __construct(FooInterface $foo)
    {
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }
}