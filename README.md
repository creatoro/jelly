Jelly is a nice little ORM for Kohana 3.1+.
The project was originally started by [Jonathan Geiger](http://jonathan-geiger.com/) and co-developed by [Paul Banks](http://blog.banksdesigns.co.uk/).

IMPORTANT
========

**Critical to know**:

* use the `3.x/master` branches for production as the `3.x/develop` branches are subject to frequent and major changes
* userguide is being updated

**Requirements**

Jelly requires the following Kohana versions per Git branch:

* `3.1/develop` and `3.1/master` branches: Kohana 3.1.3+
* `3.2/develop` and `3.2/master` branches: Kohana 3.2
* `3.3/master` branch: Kohana 3.3+

**Useful stuff**:

 * [Jelly driver for Kohana Auth](https://github.com/creatoro/jelly-auth)
 * [Report an issue or feature request](https://github.com/creatoro/jelly/issues)
 * [Leave feedback in the forum](http://forum.kohanaframework.org/discussion/9833/jelly-for-kohana-3.2-auth-driver)

**Get involved in Jelly's developement**

As Jelly has always been a community project it's development and future depends on people who are willing to put some time into it.
The easiest way to contribute is to fork the project.

Remember:

* you can directly edit files on GitHub (look for the `Edit this file` button), there's no need to get familiar with Git if you don't want to
* please follow the [Kohana conventions](http://kohanaframework.org/3.2/guide/kohana/conventions) for coding
* read the introduction to the unit tests in the guide and run them if you make changes to Jelly to minimalize the chances of introducing new bugs
* and thanks for helping Jelly become better!

## Notable Features

* **Standard support for all of the common relationships** — This includes
  `belongs_to`, `has_many`, and `many_to_many`. Pretty much standard these
  days.

* **Top-to-bottom table column aliasing** – All references to database columns
  and tables are made via their aliased names and converted transparently, on
  the fly.

* **Active testing on MySQL and SQLite** — All of the Jelly unit tests work
  100% correctly on both MySQL, SQLite and PostgresSQL databases.

* **A built-in query builder** — This features is a near direct port from
  Kohana's native ORM. I find its usage much simpler than Sprig's.

* **Extensible field architecture** — All fields in a model are represented by
  a `Field_*` class, which can be easily overridden and created for custom
  needs. Additionally, fields can implement behaviors that let the model know
  it has special ways of doing things.

* **No circular references** — Fields are well-designed to prevent the
  infinite loop problem that sometimes plagues Sprig. It's even possible to
  have same-table child/parent references out of the box without intermediate
  models.
