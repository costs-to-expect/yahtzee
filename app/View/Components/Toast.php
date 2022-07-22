<?php

namespace App\View\Components;

use Illuminate\Support\Arr;
use Illuminate\View\Component;

class Toast extends Component
{
    public array $messages;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = [
            'toast_yahtzee' => [
                [
                    'heading' => '50 points in the bag!',
                    'message' => 'Are you going to try for a Yahtzee bonus?',
                ]
            ],
            'toast_yahtzee_scratch' => [
                [
                    'heading' => 'Bye-bye 50 points!',
                    'message' => 'I\'m guessing the game isn\'t going too well.',
                ],
                [
                    'heading' => 'Bye-bye 50 points!',
                    'message' => 'Did you click the wrong button?',
                ],
                [
                    'heading' => 'Tactics!',
                    'message' => 'Was this tactical, are you Psyching out your opponents.',
                ]
            ],
            'toast_chance_scratch' => [
                [
                    'heading' => 'Oh dear!',
                    'message' => 'Why are you scratching chance, what is going on?',
                ]
            ],
            'toast_yahtzee_bonus_one' => [
                [
                    'heading' => 'Yahtzee X 2',
                    'message' => 'Have you considered giving the other players a chance?',
                ]
            ],
            'toast_yahtzee_bonus_two' => [
                [
                    'heading' => 'Yahtzee X 3',
                    'message' => 'Do the other players hate you?',
                ]
            ],
            'toast_yahtzee_bonus_three' => [
                [
                    'heading' => 'Yahtzee X 4',
                    'message' => 'GAME OVER!',
                ]
            ],
        ];
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view(
            'components.toast',
            [
                'toast_yahtzee' => Arr::random($this->messages['toast_yahtzee']),
                'toast_yahtzee_scratch' => Arr::random($this->messages['toast_yahtzee_scratch']),
                'toast_chance_scratch' => Arr::random($this->messages['toast_chance_scratch']),
                'toast_yahtzee_bonus_one' => Arr::random($this->messages['toast_yahtzee_bonus_one']),
                'toast_yahtzee_bonus_two' => Arr::random($this->messages['toast_yahtzee_bonus_two']),
                'toast_yahtzee_bonus_three' => Arr::random($this->messages['toast_yahtzee_bonus_three']),
            ]
        );
    }
}
