<?php
/*
 * Centreon is developped with GPL Licence 2.0 :
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Developped by : Julien Mathis - Romain Le Merlus 
 * 
 * The Software is provided to you AS IS and WITH ALL FAULTS.
 * Centreon makes no representation and gives no warranty whatsoever,
 * whether express or implied, and without limitation, with regard to the quality,
 * any particular or intended purpose of the Software found on the Centreon web site.
 * In no event will Centreon be liable for any direct, indirect, punitive, special,
 * incidental or consequential damages however they may arise and even if Centreon has
 * been previously advised of the possibility of such damages.
 * 
 * For information : contact@oreon-project.org
 */

class Duration
{
	function toString ($duration, $periods = null){
        if (!is_array($duration))
            $duration = Duration::int2array($duration, $periods);
        return Duration::array2string($duration);
    }
 
    function int2array ($seconds, $periods = null){        
        // Define time periods
        if (!is_array($periods)) {
            $periods = array (
                    'y'	=> 31556926,
                    'M' => 2629743,
                    'w' => 604800,
                    'd' => 86400,
                    'h' => 3600,
                    'm' => 60,
                    's' => 1
                    );
        }
 
        // Loop
        $seconds = (int) $seconds;
        foreach ($periods as $period => $value) {
            $count = floor($seconds / $value);
 
            if ($count == 0)
                continue;
 
            $values[$period] = $count;
            $seconds = $seconds % $value;
        }
 
        // Return
        if (empty($values))
            $values = null;
 
        return $values;
    }
 
    function array2string ($duration){
        if (!is_array($duration))
            return false;

        foreach ($duration as $key => $value) {
            $segment = $value . '' . $key;
            $array[] = $segment;
        }
        $str = implode(' ', $array);
        return $str;
    }
}

class Duration_hours_minutes
{
	function toString ($duration, $periods = null){
        if (!is_array($duration))
            $duration = Duration_hours_minutes::int2array($duration, $periods);
        return Duration_hours_minutes::array2string($duration);
    }
 
    function int2array ($seconds, $periods = null){
        // Define time periods
        if (!is_array($periods)) {
            $periods = array (
                    'h' => 3600,
                    'm' => 60,
                    's' => 1
                    );
        }
 
        // Loop
        $seconds = (int) $seconds;
        foreach ($periods as $period => $value) {
            $count = floor($seconds / $value);
            if ($count == 0)
                continue;
 
            $values[$period] = $count;
            $seconds = $seconds % $value;
        }
 
        // Return
        if (empty($values))
        	$values = null;
 
        return $values;
    }
 
    function array2string ($duration)	{
        if (!is_array($duration))
            return false;

        foreach ($duration as $key => $value) {
            $array[] = $value."".$key;
        }
        unset($segment);
        $str = implode(' ', $array);
        return $str;
    }
}
?>