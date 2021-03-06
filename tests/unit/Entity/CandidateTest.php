<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Candidate;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CandidateTest
 */
class CandidateTest extends WebTestCase
{
    public function testGettersAndSetters(): void
    {
        $candidate = new Candidate();

        $candidate->setEligible(false);
        $candidate->setName('test candidate');

        $this->assertFalse($candidate->getEligible());
        $this->assertEquals('test candidate', $candidate->getName());

        $candidate->setEligible(true);
        $candidate->setName('test2 candidate');

        $this->assertTrue($candidate->getEligible());
        $this->assertEquals('test2 candidate', $candidate->getName());
    }
}