# Zeta Comic Generator

An experiment in combining AI and original art to generate three panel comic strips

[Demo Site](https://comicgenerator.greenzeta.com)

## The Process

The end user writes a sentence or two describing a premise for the comic strip. The premise is included in a ChatGPT prompt that instructs it to write a three panel comic strip describing: dialog, image background, and character action. The text description of the three backgrounds is sent to the WallE image generator to produce background images. The character action is selected from a list of pre-drawn character art, featuring Alpha Zeta. Finally, all of these components are merged together to display the comic strip!

![Interface Screenshot](URLHERE "Interface")

## This Project

The code in this repo cannot be immediately run. It includes a php/mysql server component that must be set up. It is provided for review purposes only. Feel free to dig in and adapt any of this to your own project. The Alpha Zeta character art located in the `assets/character_art` directory is (c) Matthew Wilber. Feel free to use the images without modification. Attribution is appriciated but not required.


[GreenZeta.com](https://greenzeta.com)
