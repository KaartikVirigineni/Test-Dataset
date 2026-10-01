namespace FreelanceHub.Services;

public interface IAuthService
{
    string GenerateToken(int userId, string email);
    string HashPassword(string password);
    bool VerifyPassword(string password, string hash);
}