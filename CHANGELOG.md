# Changelog

The complete changelog for the Costs to Expect REST API, our changelog follows the format defined at https://keepachangelog.com/en/1.0.0/

## [1.08.0] - [2023-01-30]
### Changed
- Updated authentication to match recent changes to the Costs to Expect API.

## [1.07.1] - [2022-08-30]
### Fixed
- Corrected a type.
- Corrected combination outputs in log, missing space.
- When a player has finished their row should change in the player scores table.

## [1.07.0] - [2022-08-29]
### Changed
- Added a "How to score" section to the top of each score sheet.
- Added text explaining all the options above open games.
- Renamed the "Share" link.
- Improved the experience for new users, added text to guide the user.
### Fixed
- Corrected a validation error when credentials are invalid.
- Corrected menu links.

## [1.06.1] - [2022-08-27]
### Changed
- Updated the footer.
- Added a note about statistics coming soon.
### Fixed
- Corrected the max for ones.

## [1.06.0] - [2022-08-26]
### Added
- Added a `Registered` email, thanks the player for registering etc.
- Added an account page, shows account details and displays all the details for the two delete account options.
- Added "Delete my account", deletes all Yahtzee data and signs out the user.
- Added a `Bye` email which gets sent after the account has been deleted.
- Added error notifications when the App fails to negotiate with the API.
### Changed
- The `CreatePassword` email is delayed and only sent when the password has not been created during the sign-in process.
- The logo in the navbar goes to either home or / dependent on authentication status.

## [1.05.1] - [2022-08-25]
### Changed
- Minor change to the width of the authentication forms.
- Corrected a method name in the base controller.
### Fixed
- Validation errors on create password removed token from URI stopping the creation of the password.
- Offcanvas unable to be invoked on closed games.
- Removed default values in `App\Api\Service class`, should be using the values from the .env file.
- Corrected a bonus message if you score 63 with fewer than 5 dice.

## [1.05.0] - [2022-08-13]
### Added
- Added logging, scoring actions are logged in the API, the data will be used later for multiple features.
### Changed
- Correct the title in the HTML.
- Removed the `canonical` header.
- Added our Twitter handle to the footer.
### Fixed
- Scratching Yahtzee disables the Yahtzee bonus inputs.
- The minimum score for Three of a kind, Four of a kind and Chance is 5, not 6.

## [1.04.0] - [2022-08-10]
### Added
- Added a registration email, sent after the first step of account creation, allows continuation of account creation (create password).
### Changed
- Added a footer component.
- Added a link to create player for new registrations.
### Fixed
- Fetch the bonus message after a change to the upper section, not every n seconds.

## [1.03.0] - [2022-08-07]
### Added
- Added a new menu. switched to offcanvas rather than a static navbar.
- Added a new footer.
### Changed
- Added 'light' and 'dark' to the colour scheme and landing page.
- Adjusted the width of the authentication forms.

## [1.02.0] - [2022-08-04]
### Changed
- Removed the scratch chance option, not a relevant option.
- Minor change to the design of the bonus message container.
- Updated the bonus message text and added messages for additional combinations and scores.
- Fetch all players from the API rather than the latest ten.

## [1.01.0] - [2022-08-03]
### Added
- Added a landing page to highlight some of the features.

## [1.00.0] - [2022-08-03]
### Added
- Added account registration.

## [0.18.0] - [2022-08-01]
### Changed
- Reworked all the Javascript, no need for public versions of the files. Added a functions file and updated all the Javascript to work out the relevant URI and POST params.

## [0.17.0] - [2022-08-01]
### Added
- Added a modal that displays your final score.
### Changed
- Adjusted the timer for the player scores, down to ten seconds from thirty.

## [0.16.0] - [2022-08-01]
### Added
- Added ability to remove a player from an open game, deletes the score sheet, share token and assignment.
- Upon scoring or scratching the background color for dice is updated to highlight what has been scored.

## [0.15.0] - [2022-08-01]
### Added
- Open games can be deleted.
### Changed
- Adjusted the layout of open games ready for even more buttons.
- Updated the Bootstrap theme, added 'danger'.
- Adjusted a couple of messages relating to the upper bonus.

## [0.14.0] - [2022-07-31]
### Added
- Added a message about your bonus!

## [0.13.0] - [2022-07-31]
### Added
- Added pagination to the games list, we can now see all games and how many we have played.
- Added additional toast messages options.
### Changed
- Minor change to the layout of open games ready for more buttons.

## [0.12.0] - [2022-07-30]
### Added
- Added a "Complete & Play Again" button, sets up a new game with the same players.
### Changed
- Increased the size of all scoring checkboxes.
- Increased the spacing between Yahtzee bonus checkboxes.
- Tweaked the layout of the Yahtzee bonus section
### Fixed
- Allow a player to score zero, reworked the scoring logic to allow for this.

## [0.11.0] - [2022-07-28]
### Changed
- Added the player name to the public share score sheets.
- Updated all scoring functions to allow toasts.
- Refactoring of the score sheet javascript to support new functions and new features.
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