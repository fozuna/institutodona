<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\PlanoAcaoTaskModel;

class PlanoAcaoTaskTest extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        // Mock DB connection for model initialization
        $dbMock = $this->createMock(\PDO::class);
        $this->model = new PlanoAcaoTaskModel($dbMock);
    }

    public function testPrazoStatusRed()
    {
        $yesterday = (new \DateTime('yesterday'))->format('Y-m-d');
        $status = $this->model->getPrazoStatus($yesterday, 'A Fazer');
        $this->assertEquals('red', $status, "Yesterday should be red");
    }

    public function testPrazoStatusYellowToday()
    {
        $today = (new \DateTime('today'))->format('Y-m-d');
        $status = $this->model->getPrazoStatus($today, 'A Fazer');
        $this->assertEquals('yellow', $status, "Today should be yellow (<= 2 days)");
    }

    public function testPrazoStatusYellowTomorrow()
    {
        $tomorrow = (new \DateTime('+1 day'))->format('Y-m-d');
        $status = $this->model->getPrazoStatus($tomorrow, 'A Fazer');
        $this->assertEquals('yellow', $status, "Tomorrow should be yellow (<= 2 days)");
    }

    public function testPrazoStatusYellowNextDay()
    {
        $nextDay = (new \DateTime('+2 days'))->format('Y-m-d');
        $status = $this->model->getPrazoStatus($nextDay, 'A Fazer');
        $this->assertEquals('yellow', $status, "Day after tomorrow should be yellow (<= 2 days)");
    }

    public function testPrazoStatusGreen()
    {
        $future = (new \DateTime('+3 days'))->format('Y-m-d');
        $status = $this->model->getPrazoStatus($future, 'A Fazer');
        $this->assertEquals('green', $status, "3 days from now should be green");
    }

    public function testPrazoStatusGrayCompleted()
    {
        $yesterday = (new \DateTime('yesterday'))->format('Y-m-d');
        $status = $this->model->getPrazoStatus($yesterday, 'Concluído');
        $this->assertEquals('gray', $status, "Completed tasks should be gray regardless of date");
    }

    public function testPrazoStatusGrayNoDate()
    {
        $status = $this->model->getPrazoStatus(null, 'A Fazer');
        $this->assertEquals('gray', $status, "Tasks without date should be gray");
    }
}
