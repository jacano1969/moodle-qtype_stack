<?php
// This file is part of Stack - http://stack.bham.ac.uk//
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(__FILE__) . '/../../../locallib.php');
require_once(dirname(__FILE__) . '/../cassession.class.php');

class STACK_CAS_CasSessionTest
extends UnitTestCase {

    function Get_valid($cs, $val) {

        if (is_array($cs)) {
            $s1=array();
            foreach ($cs as $s) {
                $s1[] = new STACK_CAS_CasString($s);
            }
        } else {
            $s1 = null;
        }

        $at1 = new STACK_CAS_CasSession($s1);
        $this->assertEqual($val, $at1->Get_valid());
    }

    public function testGet_valid() {

        $a1=array('x^2', '(x+1)^2');
        $a2=array('x^2', 'x+1)^2');

        $cases =  array(
                array(null, true),
                array($a1, true),
                array($a2, false)
            );

        foreach ($cases as $case) {
           $this->Get_valid($case[0], $case[1]);
        }

    }

}


class STACK_CAS_CasSession_ExceptionTest 
extends UnitTestCase {
    public function Exception($a, $b, $c, $d, $e, $f) {
        $this->expectException();
        $at1 = new STACK_CAS_CasSession($a, $b, $c, $d, $e, $f);
        $at1->Get_valid();
    }

    public function testException() {
    // __construct($session, $options = null, $seed=null, $securityLevel='s', $syntax=true, $stars=false)
        $pref = new STACK_options();

        $cases =array(
            array("x=1", false, false, false, false, false),
            array(array(), null, false, false, false, false),
            array(array(1, 2, 3), null, false, false, false, false),
            array(null, 123, false, false, false, false),
            array(null, $pref, "abc", false, false, false),
            array(null, null, 123, false, false, false),
            array(null, null, 123, 'z', false, false),
            array(null, null, 123, 't', 1, false),
            array(null, null, 123, 't', false, 1),
        );

        foreach ($cases as $case) {
            $this->Exception($case[0], $case[1], $case[2], $case[3], $case[4], $case[5]);
        }

    }
}