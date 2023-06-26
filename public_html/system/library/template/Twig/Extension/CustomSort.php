<?php
class Twig_Extension_CustomSort extends Twig_Extension
{

    public function getName()
    {
        return 'custom_sort';
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('csort', function ($trav, array $options = []) {
                return $this->customSort($trav, $options);
            }, ['is_variadic' => true]),
        );
    }

    /**
     * Custom Sorter for twig
     * The first option is always the compare function to use.
     * Customer compare functions are not yet supported.
     * The other arguments are the keys of the multi-dimensional array to traverse down to.
     *
     * If you want to compare $a['some']['deep]['level'] to $a['some']['deep]['level']
     * use {{ data|csort( 'strcasecmp', 'some', 'deep', 'level') }}
     *
     * @param $array array Array to be sorted
     * @param $options array Levels of the multidimensial array to be sorted
     * @return array The Sorted array
     */
    public function customSort($array, $options)
    {
        usort($array, function ($a, $b) use ($options) {
            $sortFunc = array_shift($options);
            return $sortFunc($this->getToLevel($a, $options), $this->getToLevel($b, $options));
        });
        return $array;
    }

    /**
     * Recursive function to get to the right element of the multi-dimensial array to compare
     *
     * @param $trav
     * @param array $levels
     * @return mixed
     */
    protected function getToLevel($trav, array $levels = [])
    {
        if (count($levels) > 1) {
            $lvl = array_shift($levels);
            return $this->getToLevel($trav[$lvl], $levels);
        } elseif (count($levels) === 1) {
            $lvl = array_shift($levels);
            return $trav[$lvl];
        } else {
            return $trav;
        }
    }
}
