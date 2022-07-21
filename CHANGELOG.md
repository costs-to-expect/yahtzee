# Changelog

The complete changelog for the Costs to Expect REST API, our changelog follows the format defined at https://keepachangelog.com/en/1.0.0/

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