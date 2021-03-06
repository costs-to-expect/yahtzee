# Changelog

The complete changelog for the Costs to Expect REST API, our changelog follows the format defined at https://keepachangelog.com/en/1.0.0/

## [0.11.0] - [2022-07-28]
### Changed
- Added the player name to the public share score sheets.
- Updated all scoring functions to allow toasts.
- Refacoring of the score sheet javascript to support new functions and new features.
- Cleaned up the base controller, removed a couple of pointless methods.

## [0.10.0] - [2022-07-28]
### Added
- Added player turns to the player scores table.

### Changed
- Removed some code scoring duplication in the `Game` and `Share` controllers.

### Fixed
- Disable the Yahtzee bonus inputs after player has made all their turns, page load and via Ajax.

## [0.9.0] - [2022-07-26]
### Added
- Added a game detail page, for now, no extra data or statistics.

### Changed
- More details added to the player scores table at the bottom of the score sheet.
- Start game redirects to the game detail page, no need to wait for API cache to be invalidated.

### Fixed
- The `complete` and `add players` buttons should only display when a game is incomplete.

## [0.8.0] - [2022-07-24]
### Added
- Confetti, who doesn't like confetti?

### Changed
- Slightly decreased the time before a save happens.

## [0.7.0] - [2022-07-23]
### Added
- Added a table to the bottom of each score sheet, shows all the player scores, delayed by thirty seconds.

### Changed
- Changed the URIs for all the share pages.

### Fixed
- You can only score a Yahtzee bonus when a Yahtzee has been scored.

## [0.6.0] - 2022-07-22
### Added
- Added toast messages for scoreboard actions, toasts are selected from a random list for each action.

### Changed
- Added the @copyright headers for any files not part of a default Laravel project.
- Don't load any Javascript (scoring) or the toasts view component when looking at the score sheet for a completed game.

### Fixed
- Delete the share tokens when a game completes.

## [0.5.0] - 2022-7-22
### Added
- Added a complete game action, sets the winner, player scores and updates the game status.

### Changed
- Show the player scores next to open games.
- Updated the layout of completed games to match open games.

## [0.4.0] - 2022-07-21
### Added
- Added a public score sheet for players, accessible without an account via a unique token.

### Changed
- Creating a game and adding additional players to a game creates a unique share token for each player.
- Redirect to the home page after game creation and adding additional players, not the games list.
- Show the link for the public score sheet next to each player name, it can then be easily shared by the game starter.
- Adjusted the spacing and sizes of inputs in the upper section to increase accessibility.
- Increased the spacing between open games.

## [0.3.0] - Initial playable release

Initial release of the Yahtzee game scorer which is powered by the Costs to Expect API. If you have an 
account on the Costs to Expect API, you can set your players and track your games.

Many features are planned, this is very much a work in progress, but it is usable.