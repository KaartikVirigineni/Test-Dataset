FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS base
WORKDIR /app
EXPOSE 80

FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src
COPY ["LeadScorePro.csproj", "./"]
RUN dotnet restore "LeadScorePro.csproj"
COPY . .
RUN dotnet build "LeadScorePro.csproj" -c Release -o /app/build

FROM build AS publish
RUN dotnet publish "LeadScorePro.csproj" -c Release -o /app/publish /p:UseAppHost=false

FROM base AS final
WORKDIR /app
COPY --from=publish /app/publish .
RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*
RUN mkdir -p /app/data
ENTRYPOINT ["dotnet", "LeadScorePro.dll"]