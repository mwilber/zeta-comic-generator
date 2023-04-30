# Zeta Comic Generator

An experiment in combining AI and original art to generate three panel comic strips

[Demo Site](https://comicgenerator.greenzeta.com)

## The Process

Write a sentence or two describing a premise for the comic strip. ChatGPT takes the premise and writes a three panel comic strip describing: dialog, background scenery, and character action. The text description of the background scenery is sent to the Dall-E image generator to produce background images. The character action is selected from a list of pre-drawn character art featuring [Alpha Zeta](https://greenzeta.com/project/illustrations/), the alien mascot of [GreenZeta.com](https://greenzeta.com). All of these assets are merged together into a comic strip!

![Interface Screenshot](https://greenzeta.com/wp-content/uploads/2023/04/Screen-Shot-2023-04-28-at-9.00.59-PM.png "Interface")

## This Project

The code in this repo cannot be immediately run. It includes a php/mysql server component that must be set up. It is provided for review purposes only. Feel free to dig in and adapt any of this to your own project. The Alpha Zeta character art located in the `assets/character_art` directory is (c) Matthew Wilber. Feel free to use the images without modification. Attribution is appriciated but not required.


[GreenZeta.com](https://greenzeta.com)
