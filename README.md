# Container

A lightweight PHP dependency injection container.

### Installation
```bash
composer require flashytime/container
```

### Usage
##### Create a container
```php
use Flashytime\Container\Container;
$container = new Container();
```

##### Register a closure
```php
$this->container->set('hello', function () {
    return 'Hello World!';
});
echo $this->container->get('hello'); //Hello World!
```
```php
$this->container->set('name', function () {
    return 'Mocha';
});
$this->container->set('mocha', function ($container) {
    return sprintf('Hello %s!', $container->get('name'));
});
echo $this->container->get('mocha'); //Hello Mocha!
```

##### Register a instance or class
```php
$this->container->set('foo', function () {
    return new Foo();
});
```
or
```php
$this->container->set('foo', new Foo());
```
or
```php
$this->container->set('foo', 'Flashytime\Container\Tests\Foo');
```
or
```php
$this->container->set('foo', Flashytime\Container\Tests\Foo::class);
```
##### Get the entry
```php
$this->container->get('foo');
```

##### Register a singleton
```php
$this->container->setSingleton('foo', Flashytime\Container\Tests\Foo::class);
```

##### Dependency Injection
```php
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
```
```php
$this->container->set(FooInterface::class, Foo::class);
$this->container->set('bar', Bar::class);
$bar = $this->container->get('bar');
var_dump($bar instanceof Bar); //true
var_dump($bar->getFoo() instanceof Foo); //true
```

see [tests](https://github.com/flashytime/container/blob/master/tests/ContainerTest.php) to get more usages.

### License
[MIT](https://opensource.org/licenses/MIT)