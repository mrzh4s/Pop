# Contributing to Pop Framework

Thank you for considering contributing to Pop Framework! This document provides guidelines and instructions for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Enhancements](#suggesting-enhancements)
- [Testing Guidelines](#testing-guidelines)

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

**Our Pledge:**
- Be respectful and inclusive
- Accept constructive criticism gracefully
- Focus on what is best for the community
- Show empathy towards other community members

## How Can I Contribute?

### Types of Contributions

We welcome contributions in the following areas:

1. **Bug Fixes** - Fix issues in the framework core
2. **New Features** - Add new functionality to the framework
3. **Documentation** - Improve or add documentation
4. **Tests** - Add or improve test coverage
5. **Examples** - Add example implementations
6. **Performance** - Optimize existing code

## Development Setup

### Prerequisites

- PHP 8.4 or higher
- Composer 2.x
- Node.js 18+ and npm
- Git
- A database (SQLite, PostgreSQL, or MySQL)

### Setup Steps

1. **Fork the repository**
   ```bash
   # Click the "Fork" button on GitHub
   ```

2. **Clone your fork**
   ```bash
   git clone https://github.com/YOUR_USERNAME/Pop.git
   cd Pop
   ```

3. **Add upstream remote**
   ```bash
   git remote add upstream https://github.com/mrzh4s/Pop.git
   ```

4. **Install PHP dependencies**
   ```bash
   composer install
   ```

5. **Install frontend dependencies**
   ```bash
   npm install
   ```

6. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

7. **Run migrations**
   ```bash
   # Migrations auto-run on bootstrap, just start the server
   php -S localhost:8000 -t Infrastructure/Http/Public
   ```

8. **Start development server**
   ```bash
   # Terminal 1: PHP server
   ./run.sh

   # Terminal 2: Vite dev server
   npm run dev
   ```

## Coding Standards

### PHP Code Style

We follow **PSR-12** coding standards with some additional guidelines:

#### General Rules

1. **Strict Types**
   ```php
   <?php
   declare(strict_types=1);
   ```
   All PHP files must start with strict types declaration.

2. **Type Hints**
   ```php
   // Good
   public function getUser(int $id): ?User
   {
       return $this->repository->find($id);
   }

   // Bad
   public function getUser($id)
   {
       return $this->repository->find($id);
   }
   ```

3. **Return Types**
   - Always declare return types
   - Use `void` for methods that don't return values
   - Use `null` in union types when appropriate

4. **Properties**
   ```php
   // Use readonly properties where possible (PHP 8.4+)
   class User
   {
       public function __construct(
           public readonly int $id,
           public readonly string $name,
       ) {}
   }
   ```

#### Naming Conventions

- **Classes**: PascalCase (e.g., `UserRepository`, `LoginHandler`)
- **Methods**: camelCase (e.g., `getUserById`, `handleRequest`)
- **Properties**: camelCase (e.g., `$userId`, `$emailAddress`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `MAX_ATTEMPTS`, `DEFAULT_TIMEOUT`)
- **Namespaces**: Follow PSR-4 structure

#### Documentation

```php
/**
 * Authenticate a user with credentials
 *
 * @param LoginCommand $command The login credentials
 * @return LoginResponse The authentication result
 * @throws InvalidCredentialsException If credentials are invalid
 */
public function authenticate(LoginCommand $command): LoginResponse
{
    // Implementation
}
```

### JavaScript/React Code Style

1. **Use modern ES6+ syntax**
2. **Functional components with hooks**
3. **Meaningful component and variable names**
4. **Comments for complex logic**

```jsx
// Good
export default function UserList({ users, filters }) {
    const [selectedUser, setSelectedUser] = useState(null);

    return (
        <div className="user-list">
            {/* Component content */}
        </div>
    );
}
```

### File Organization

#### Framework Core (`Framework/`)
- Keep framework code separate from application code
- Each component should have a single responsibility
- Use subdirectories for related classes (Http/, Database/, Security/)

#### Features (`Features/`)
- Follow Vertical Slice Architecture
- Each feature is self-contained
- Structure: `Features/{Feature}/{Action}/` pattern
- Shared code goes in `Features/{Feature}/Shared/`

```
Features/
└── Users/
    ├── CreateUser/
    │   ├── CreateUserCommand.php
    │   ├── CreateUserHandler.php
    │   ├── CreateUserController.php
    │   └── CreateUserResponse.php
    └── Shared/
        └── Domain/
            └── User.php
```

## Pull Request Process

### Before Submitting

1. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/bug-description
   ```

2. **Make your changes**
   - Write clean, well-documented code
   - Follow coding standards
   - Add tests for new functionality
   - Update documentation if needed

3. **Run tests**
   ```bash
   composer test
   ```

4. **Commit your changes**
   ```bash
   git add .
   git commit -m "Add feature: description of your changes"
   ```

   **Commit Message Format:**
   ```
   <type>: <subject>

   <body>

   <footer>
   ```

   **Types:**
   - `feat`: New feature
   - `fix`: Bug fix
   - `docs`: Documentation changes
   - `test`: Adding or updating tests
   - `refactor`: Code refactoring
   - `perf`: Performance improvements
   - `chore`: Maintenance tasks

   **Example:**
   ```
   feat: Add cache abstraction layer

   Implement a cache abstraction with file and memory drivers.
   Includes cache tags support and automatic expiration.

   Closes #123
   ```

### Submitting Pull Request

1. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

2. **Create Pull Request**
   - Go to the original repository on GitHub
   - Click "New Pull Request"
   - Select your feature branch
   - Fill out the PR template

3. **PR Title Format**
   ```
   [Type] Brief description
   ```
   Example: `[Feature] Add queue system` or `[Fix] Resolve CSRF token validation`

4. **PR Description Should Include:**
   - What changes were made
   - Why the changes were necessary
   - How to test the changes
   - Related issue numbers (if any)
   - Screenshots (for UI changes)

5. **Code Review**
   - Address reviewer feedback
   - Push additional commits if needed
   - Be open to suggestions

## Reporting Bugs

### Before Reporting

- Check if the bug has already been reported
- Verify the bug exists in the latest version
- Collect information about your environment

### Bug Report Template

```markdown
**Description:**
A clear description of the bug.

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Behavior:**
What you expected to happen.

**Actual Behavior:**
What actually happened.

**Environment:**
- PHP Version: 8.4.x
- Operating System: Ubuntu 22.04
- Database: PostgreSQL 15
- Framework Version: 1.0.0

**Additional Context:**
Any other relevant information, logs, or screenshots.
```

### Where to Report

- **GitHub Issues**: [https://github.com/mrzh4s/Pop/issues](https://github.com/mrzh4s/Pop/issues)

## Suggesting Enhancements

### Enhancement Proposal Template

```markdown
**Feature Description:**
Brief description of the proposed feature.

**Problem it Solves:**
What problem does this feature address?

**Proposed Solution:**
How should this feature work?

**Alternatives Considered:**
Other approaches you've considered.

**Additional Context:**
Any mockups, examples, or references.
```

### Discussion

- Open an issue with the `enhancement` label
- Participate in the discussion
- Be open to alternative approaches

## Testing Guidelines

### Test Structure

```php
<?php
declare(strict_types=1);

namespace Tests\Unit\Framework\Http;

use PHPUnit\Framework\TestCase;
use Framework\Http\Router;

class RouterTest extends TestCase
{
    public function test_it_registers_get_route(): void
    {
        // Arrange
        $router = new Router();

        // Act
        $router->get('/users', 'UserController@index');

        // Assert
        $this->assertTrue($router->hasRoute('GET', '/users'));
    }
}
```

### Testing Requirements

1. **Unit Tests** for framework core components
2. **Feature Tests** for complete feature slices
3. **Test Coverage**: Aim for 70%+ coverage on new code
4. **Test Names**: Use descriptive names (`test_it_does_something`)

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage

# Run specific test file
./vendor/bin/phpunit Tests/Unit/Framework/Http/RouterTest.php
```

## Branch Naming

- `feature/feature-name` - New features
- `fix/bug-description` - Bug fixes
- `docs/what-changed` - Documentation updates
- `test/what-tested` - Test additions
- `refactor/what-refactored` - Code refactoring

## Review Process

1. **Automated Checks**: CI/CD must pass
2. **Code Review**: At least one maintainer approval
3. **Testing**: All tests must pass
4. **Documentation**: Updated if necessary
5. **Changelog**: Entry added if user-facing changes

## Questions?

If you have questions about contributing:

- Open a discussion on GitHub
- Check existing issues and PRs
- Review the documentation in [CLAUDE.md](CLAUDE.md)

## Recognition

Contributors will be recognized in:
- CHANGELOG.md
- GitHub contributors page
- Release notes

Thank you for contributing to Pop Framework! 🎉
