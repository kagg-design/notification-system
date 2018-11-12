# How to work on this exercise

1. Fork this repository
2. Share your work with the interviewer once it's ready

# Purpose

Create a free-subject WordPress plugin which makes use of the WordPress API for providing REST API.

The purpose of the API is up to you.  
In case of a lack of idea, you can use the example below.

# Requirements

1. develop and implement a set of CRUD REST API in the `/otgs/SDT001/v1` REST namespace
2. Create a JavaScript interface for consuming the REST API
3. Provide a user interface (depending on the purpose of the API, consider where it's appropriate serving the interface on the back-end, front-end or both)
4. Cover the code with unit tests
5. Respect the WordPress coding standards
6. Make use of OOP
7. Keep an eye on the quality of the code

## Optional requirements

- Using of ECMAScript6
  - If using ECMAScript6, avoid using jQuery
- Making the code compatible with PHP 5.2.4+ as per the [WordPress minimum requirements](https://wordpress.org/about/requirements/)

## Example

The plugin provides features for creating, searching and booking apartments (similar to Airbnb).

### Front-end

Users can search apartments by name, date availability, type (room, apartment, flat, house), and the number of beds.

### Back-end (admin)

Admins can add, modify or delete apartments.