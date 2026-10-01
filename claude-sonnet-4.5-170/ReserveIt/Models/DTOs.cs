using System.ComponentModel.DataAnnotations;

namespace ReserveIt.Models;

public class LoginRequest
{
    [Required]
    [EmailAddress]
    public string Email { get; set; } = string.Empty;

    [Required]
    public string Password { get; set; } = string.Empty;
}

public class RegisterRequest
{
    [Required]
    [EmailAddress]
    public string Email { get; set; } = string.Empty;

    [Required]
    public string Name { get; set; } = string.Empty;

    [Required]
    [MinLength(6)]
    public string Password { get; set; } = string.Empty;
}

public class AuthResponse
{
    public string Token { get; set; } = string.Empty;
    public string Email { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string Role { get; set; } = string.Empty;
}

public class CreateReservationRequest
{
    [Required]
    public int TableId { get; set; }

    [Required]
    public DateTime ReservationDate { get; set; }

    [Required]
    [Range(15, 480)]
    public int DurationMinutes { get; set; }

    [Required]
    [Range(1, 20)]
    public int PartySize { get; set; }

    public string? SpecialRequests { get; set; }
}

public class UpdateReservationRequest
{
    public DateTime? ReservationDate { get; set; }
    public int? DurationMinutes { get; set; }
    public int? PartySize { get; set; }
    public string? Status { get; set; }
    public string? SpecialRequests { get; set; }
}

public class ReservationResponse
{
    public int Id { get; set; }
    public int UserId { get; set; }
    public string UserName { get; set; } = string.Empty;
    public int TableId { get; set; }
    public string TableNumber { get; set; } = string.Empty;
    public DateTime ReservationDate { get; set; }
    public int DurationMinutes { get; set; }
    public int PartySize { get; set; }
    public string Status { get; set; } = string.Empty;
    public string? SpecialRequests { get; set; }
    public DateTime CreatedAt { get; set; }
}