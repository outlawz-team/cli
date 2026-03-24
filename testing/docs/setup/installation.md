# Installation

## Server requirements

* Your document root must be configurable (_most_ local development tools and webhosts should support this)
* PHP >= 8.4 with the following extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, Tokenizer, XML
* Composer
* WP-CLI

## Installing Radicle

Radicle requires [purchasing a Radicle license](/radicle/) to get access to the codebase. Once Radicle has been purchased, you can use the [Roots Dashboard](https://dashboard.roots.io/) to generate an invitation to the GitHub repository or download the latest release.

After you've accepted the invitation to the GitHub repository, navigate to the [releases to download the latest version of Radicle](https://github.com/roots/radicle/releases).

> [!TIP]
> Are you building new sites often? Create your own private repo with the Radicle codebase and modify it to fit your needs. Radicle should be treated as a boilerplate/starting point, and not a framework.

Although it is possible to retrofit an existing project with Radicle, we recommend using Radicle on a new project.

Unzip the contents of the latest Radicle release into your new project's directory. Make sure all of the hidden files (such as `.gitignore`) are included in your project directory.
