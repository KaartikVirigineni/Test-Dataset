using ExperimentHub.Data;
using ExperimentHub.Models;
using Microsoft.Data.Sqlite;
using Microsoft.IdentityModel.Tokens;
using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Security.Cryptography;
using System.Text;

namespace ExperimentHub.Services;

public class AuthService
{
    private readonly DatabaseService _dbService;
    private readonly IConfiguration _configuration;

    public AuthService(DatabaseService dbService, IConfiguration configuration)
    {
        _dbService = dbService;
        _configuration = configuration;
    }

    public User Register(string username, string email, string password)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        // Check if user exists
        var checkCommand = connection.CreateCommand();
        checkCommand.CommandText = "SELECT COUNT(*) FROM Users WHERE Username = $username OR Email = $email";
        checkCommand.Parameters.AddWithValue("$username", username);
        checkCommand.Parameters.AddWithValue("$email", email);
        
        var count = (long)checkCommand.ExecuteScalar()!;
        if (count > 0)
            throw new InvalidOperationException("Username or email already exists");

        // Hash password
        var passwordHash = HashPassword(password);

        // Insert user
        var command = connection.CreateCommand();
        command.CommandText = @"
            INSERT INTO Users (Username, Email, PasswordHash, CreatedAt)
            VALUES ($username, $email, $passwordHash, $createdAt);
            SELECT last_insert_rowid();
        ";
        command.Parameters.AddWithValue("$username", username);
        command.Parameters.AddWithValue("$email", email);
        command.Parameters.AddWithValue("$passwordHash", passwordHash);
        command.Parameters.AddWithValue("$createdAt", DateTime.UtcNow.ToString("o"));

        var userId = (long)command.ExecuteScalar()!;

        return new User
        {
            Id = (int)userId,
            Username = username,
            Email = email,
            PasswordHash = passwordHash,
            CreatedAt = DateTime.UtcNow
        };
    }

    public string Login(string username, string password)
    {
        using var connection = _dbService.GetConnection();
        connection.Open();

        var command = connection.CreateCommand();
        command.CommandText = "SELECT Id, Username, PasswordHash FROM Users WHERE Username = $username";
        command.Parameters.AddWithValue("$username", username);

        using var reader = command.ExecuteReader();
        if (!reader.Read())
            throw new UnauthorizedAccessException("Invalid username or password");

        var userId = reader.GetInt32(0);
        var storedHash = reader.GetString(2);

        if (!VerifyPassword(password, storedHash))
            throw new UnauthorizedAccessException("Invalid username or password");

        return GenerateJwtToken(userId, username);
    }

    private string HashPassword(string password)
    {
        using var sha256 = SHA256.Create();
        var hashedBytes = sha256.ComputeHash(Encoding.UTF8.GetBytes(password));
        return Convert.ToBase64String(hashedBytes);
    }

    private bool VerifyPassword(string password, string hash)
    {
        var passwordHash = HashPassword(password);
        return passwordHash == hash;
    }

    private string GenerateJwtToken(int userId, string username)
    {
        var secret = _configuration["JwtSettings:Secret"] ?? "ThisIsAVerySecureSecretKeyForJWTTokenGeneration123456";
        var issuer = _configuration["JwtSettings:Issuer"] ?? "ExperimentHub";
        var audience = _configuration["JwtSettings:Audience"] ?? "ExperimentHubUsers";
        var expiryMinutes = int.Parse(_configuration["JwtSettings:ExpiryMinutes"] ?? "60");

        var securityKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(secret));
        var credentials = new SigningCredentials(securityKey, SecurityAlgorithms.HmacSha256);

        var claims = new[]
        {
            new Claim(ClaimTypes.NameIdentifier, userId.ToString()),
            new Claim(ClaimTypes.Name, username),
            new Claim(JwtRegisteredClaimNames.Jti, Guid.NewGuid().ToString())
        };

        var token = new JwtSecurityToken(
            issuer: issuer,
            audience: audience,
            claims: claims,
            expires: DateTime.UtcNow.AddMinutes(expiryMinutes),
            signingCredentials: credentials
        );

        return new JwtSecurityTokenHandler().WriteToken(token);
    }
}