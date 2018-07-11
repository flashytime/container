<?php
/**
 * 依赖注入容器
 * Author: flashytime <myflashytime@gmail.com>
 * Date: 2018/7/3 20:26
 */

namespace Flashytime\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use Closure;

class Container implements ContainerInterface
{
    /**
     * 注册到容器的entry集合，包括 instance|callable|closure|object|class 等
     * @var array
     */
    private $definitions = [];

    /**
     * 存储单例标记和单例
     * @var array
     */
    private $singletons = [];

    /**
     * 缓存ReflectionClass
     * @var array
     */
    private $reflections = [];

    /**
     * 缓存依赖
     * @var array
     */
    private $dependencies = [];

    /**
     * 从容器里获取一个entry
     * @param string $id
     * @param array $parameters
     * @return mixed
     * @throws NotFoundException
     */
    public function get($id, array $parameters = [])
    {
        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        if ($this->has($id)) {
            array_unshift($parameters, $this);
            $object = call_user_func_array($this->definitions[$id], $parameters);

            //如果有单例标记，则存储单例
            if (array_key_exists($id, $this->singletons)) {
                $this->singletons[$id] = $object;
            }

            return $object;
        }

        throw new NotFoundException(sprintf('No entry was found for "%s" identifier', $id));
    }

    /**
     * 判断容器里是否注册过某个entry
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->definitions[$id]);
    }

    /**
     * 注册一个entry到容器中
     * @param $id
     * @param null $concrete
     * @param bool $singleton
     */
    public function set($id, $concrete = null, $singleton = false)
    {
        if (is_null($concrete)) {
            $concrete = $id;
        }

        if ($singleton) {
            $this->singletons[$id] = null;
        } else {
            unset($this->singletons[$id]);
        }

        $this->definitions[$id] = $this->getClosure($concrete);
    }

    /**
     * 注册一个单例entry到容器中
     * @param $id
     * @param null $concrete
     */
    public function setSingleton($id, $concrete = null)
    {
        $this->set($id, $concrete, true);
    }

    /**
     * 获取一个闭包
     * @param $class
     * @return Closure
     */
    protected function getClosure($class)
    {
        /**
         * @param $container $this
         * @param $parameters
         * @return Closure
         */
        return function ($container, ...$parameters) use ($class) {
            if (is_callable($class) || $class instanceof Closure) {
                return $class($container, ...$parameters);
            } elseif (is_object($class)) {
                return $class;
            }

            return $container->build($class, $parameters);
        };
    }

    /**
     * 将类实例化
     * @param $class
     * @param array $parameters
     * @return mixed
     */
    protected function build($class, array $parameters = [])
    {
        $args = [];
        list($reflection, $dependencies) = $this->getDependencies($class);
        foreach ($dependencies as $index => $dependency) {
            if (!$this->has($dependency)) {
                $this->set($dependency);
            }
            $args[$dependency] = $this->get($dependency);
        }

        return $reflection->newInstanceArgs(array_merge($args, $parameters));
    }

    /**
     * 获取类的反射和依赖，同时缓存
     * @param $class
     * @return array
     * @throws ContainerException
     */
    protected function getDependencies($class)
    {
        if (isset($this->reflections[$class])) {
            return [$this->reflections[$class], $this->dependencies[$class]];
        }

        $dependencies = [];
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new ContainerException(sprintf('"%s" is not instantiable', $class));
        }

        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->getClass()) {
                    $dependencies[] = $parameter->getClass()->getName();
                }
            }
        }
        $this->reflections[$class] = $reflection;
        $this->dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }
}
