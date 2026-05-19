<?php
namespace Core\Foundation;

class HookManager {
    protected array $actions = [];
    protected array $filters = [];

    public function addAction(string $tag, callable|array|string $function, int $priority = 10): void {
        $this->actions[$tag][$priority][] = $function;
    }

    public function doAction(string $tag, ...$args): void {
        if (!isset($this->actions[$tag])) return;
        ksort($this->actions[$tag]);
        foreach ($this->actions[$tag] as $priority => $functions) {
            foreach ($functions as $function) {
                call_user_func_array($function, $args);
            }
        }
    }

    public function addFilter(string $tag, callable|array|string $function, int $priority = 10): void {
        $this->filters[$tag][$priority][] = $function;
    }

    public function applyFilters(string $tag, mixed $value, ...$args): mixed {
        if (!isset($this->filters[$tag])) return $value;
        ksort($this->filters[$tag]);
        foreach ($this->filters[$tag] as $priority => $functions) {
            foreach ($functions as $function) {
                $callArgs = array_merge([$value], $args);
                $value = call_user_func_array($function, $callArgs);
            }
        }
        return $value;
    }
}
