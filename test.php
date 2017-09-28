<?php

require('./vendor/autoload.php');

use function Phantasy\Types\{product, sum};

function curry(callable $callable)
{
    $ref = new \ReflectionFunction($callable);
    $recurseFunc = function (...$args) use ($callable, $ref, &$recurseFunc) {
        if (count($args) >= $ref->getNumberOfRequiredParameters()) {
            return call_user_func_array($callable, $args);
        } else {
            return function (...$args2) use ($args, &$recurseFunc) {
                return $recurseFunc(...array_merge($args, $args2));
            };
        }
    };
    return $recurseFunc;
}
function map(...$args)
{
    $map = curry(
        function (callable $f, $x) {
            if (is_callable([$x, 'map'])) {
                return call_user_func([$x, 'map'], $f);
            }
        }
    );
    return $map(...$args);
}
function cata(...$args)
{
    $cata = curry(function (callable $f, $xs) {
        return $f(map(cata($f), $xs));
    });
    return $cata(...$args);
}
function ana(...$args)
{
    $ana = curry(function (callable $f, $x) {
        return map(ana($f), $f($x));
    });
    return $ana(...$args);
}
function hylo(...$args)
{
    $hylo = curry(function (callable $f, callable $g, $x) {
        return $f(map(hylo($f, $g), $g($x)));
    });
    return $hylo(...$args);
}



$LL = sum('LinkedList', [
	'Cons' => ['head', 'tail'],
	'Nil' => []
]);

$LL->map = function (callable $f) {
	return $this->cata([
		'Cons' => function ($head, $tail) use ($f) {
			return $this->Cons($head, $f($tail));
 		},
		'Nil' => function () {
			return $this->Nil();
		}
	]);
};

$LL->isNil = function () {
	return $this->cata([
		'Cons' => function ($head, $tail) {
			return false;
		},
		'Nil' => function () {
			return true;
		}
	]);
};

$alg = function ($x) {
	return $x->isNil() ? 0 : $x->head + $x->tail;
};
$a = $LL->Cons(2, $LL->Cons(1, $LL->Nil()));
// echo cata($alg, $a);



$Prog = sum('Program', [
    'GetUsers' => ['params'],
    'ListUsers' => ['users'],
    'Const' => ['x']
]);

$Prog->map = function (callable $f) {
    return $this->cata([
        'GetUsers' => function ($params) use ($f) {
            return $this->GetUsers($f($params));
        },
        'ListUsers' => function ($users) use ($f) {
            return $this->ListUsers($f($users));
        },
        'Const' => function ($x) {
            return $this;
        }
    ]);
};

$Const = function ($x) use ($Prog) {
    return $Prog->Const(1);
};

$ListUsers = function ($users) use ($Prog) {
    return $Prog->ListUsers($users);
};

$GetUsers = function ($params) use ($Prog) {
    return $Prog->GetUsers($params);
};

$myProgram = $ListUsers($GetUsers($Const([])));
$myProgramInterpreter = function ($a) {
    switch ($a->tag) {
        case 'GetUsers':
            // $a->params is available here
            return ['User1', 'User2'];
        case 'ListUsers':
            return '<div>' . array_reduce($a->users, function ($prev, $curr) {
                return $prev . '<div>' . $curr . '</div>';
            }, '') . '</div>';
        case 'Const':
            return $a->x;
    }
};

echo cata($myProgramInterpreter, $myProgram);
